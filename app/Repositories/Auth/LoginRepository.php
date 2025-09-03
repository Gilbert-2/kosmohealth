<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Models\KycVerification;
use App\Models\UserLoginActivity;
use App\Http\Resources\AuthUser;
use App\Traits\UserLoginThrottle;
use App\Traits\TwoFactorSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginRepository
{
    use UserLoginThrottle, TwoFactorSecurity;

    protected $user;

    /**
     * Instantiate a new instance
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Authenticate user
     *
     * @param array $loginData
     * @param bool $remember
     * @return array
     */
    public function login(array $loginData, bool $remember = false): array
    {
        $this->throttleValidate();

        try {
            // Determine login method
            $user = isset($loginData['device_name'])
                ? $this->validateDeviceLogin($loginData)
                : $this->validateLogin($loginData, $remember);

            // Validate user status
            $user->validateStatus();

            // Clear throttle
            $this->throttleClear();

            // Set up two-factor security if enabled
            $this->set($user);

            // Log login activity
            $this->logLoginActivity($user, $loginData);

            // Check if KYC verification is required
            $kycRequired = $this->isKycRequired($user);

            // Prepare response
            return [
                'message'        => __('auth.login.logged_in'),
                'user'           => new AuthUser($user),
                'token'          => $user->createToken($loginData['device_name'] ?? 'web')->plainTextToken,
                'two_factor_set' => config('config.auth.two_factor_security') ? true : false,
                'kyc_required'   => $kycRequired,
                'login_time'     => now()->toIso8601String(),
                'login_method'   => isset($loginData['device_name']) ? 'api' : 'web',
                'ui_preference'  => $user->ui_preference ?? 'default',
            ];
        } catch (\Exception $e) {
            // Log failed login attempt
            \Log::error('Login failed: ' . $e->getMessage(), [
                'email' => $loginData['email'] ?? 'unknown',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate login credentials
     *
     * @param array $loginData
     * @param bool $remember
     * @return User
     */
    protected function validateLogin(array $loginData, bool $remember): User
    {
        $identifier = $loginData['email'];
        $password = $loginData['password'];

        $credentials = $this->determineCredentialType($identifier, $password);

        if (!Auth::attempt($credentials, $remember)) {
            $this->throttleUpdate();
            throw ValidationException::withMessages(['email' => __('auth.login.failed')]);
        }

        return Auth::user();
    }

    /**
     * Validate device login credentials
     *
     * @param array $loginData
     * @return User
     */
    protected function validateDeviceLogin(array $loginData): User
    {
        $identifier = $loginData['email'];
        $password = $loginData['password'];

        $user = $this->findUserByIdentifier($identifier);

        if (!$user || !Hash::check($password, $user->password)) {
            $this->throttleUpdate();
            throw ValidationException::withMessages(['email' => __('auth.login.failed')]);
        }

        return $user;
    }

    /**
     * Determine if identifier is email or username and return appropriate credentials
     *
     * @param string $identifier
     * @param string $password
     * @return array
     */
    protected function determineCredentialType(string $identifier, string $password): array
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $identifier, 'password' => $password];
        } else {
            return ['username' => $identifier, 'password' => $password];
        }
    }

    /**
     * Find user by email or username
     *
     * @param string $identifier
     * @return User|null
     */
    protected function findUserByIdentifier(string $identifier): ?User
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::whereEmail($identifier)->first();
        } else {
            return User::whereUsername($identifier)->first();
        }
    }

    /**
     * Log login activity
     *
     * @param User $user
     * @param array $loginData
     * @return void
     */
    protected function logLoginActivity(User $user, array $loginData): void
    {
        try {
            UserLoginActivity::create([
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'login_at' => now(),
                'device_name' => $loginData['device_name'] ?? 'web',
                'meta' => [
                    'browser' => $this->getBrowserInfo(),
                    'platform' => $this->getPlatformInfo(),
                    'location' => $this->getLocationInfo(),
                ]
            ]);
        } catch (\Exception $e) {
            // Log error but don't interrupt login process
            Log::error('Failed to log login activity: ' . $e->getMessage());
        }
    }

    /**
     * Check if KYC verification is required for the user
     *
     * @param User $user
     * @return bool
     */
    protected function isKycRequired(User $user): bool
    {
        // Check if KYC is enabled in config
        if (!config('config.kyc.enabled', false)) {
            return false;
        }

        // Check if user has completed KYC
        $kycStatus = KycVerification::where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        return !$kycStatus;
    }

    /**
     * Get browser information from user agent
     *
     * @return string
     */
    protected function getBrowserInfo(): string
    {
        $userAgent = request()->userAgent();
        $browserInfo = 'Unknown Browser';

        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
            $browserInfo = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browserInfo = 'Mozilla Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browserInfo = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browserInfo = 'Apple Safari';
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $browserInfo = 'Opera';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browserInfo = 'Microsoft Edge';
        }

        return $browserInfo;
    }

    /**
     * Get platform information from user agent
     *
     * @return string
     */
    protected function getPlatformInfo(): string
    {
        $userAgent = request()->userAgent();
        $platform = 'Unknown Platform';

        if (preg_match('/windows|win32|win64/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Mac OS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        return $platform;
    }

    /**
     * Get location information from IP address
     *
     * @return array
     */
    protected function getLocationInfo(): array
    {
        // In a real implementation, you might use a geolocation service
        // For now, we'll just return the IP address
        return [
            'ip' => request()->ip(),
            'country' => 'Unknown',
            'city' => 'Unknown'
        ];
    }

    /**
     * Logout user
     *
     * @return void
     */
    public function logout(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Log logout activity
            try {
                UserLoginActivity::where('user_id', $user->id)
                    ->whereNull('logout_at')
                    ->orderBy('created_at', 'desc')
                    ->first()
                    ->update(['logout_at' => now()]);
            } catch (\Exception $e) {
                Log::error('Failed to log logout activity: ' . $e->getMessage());
            }

            // Revoke current token if using Sanctum
            if (request()->bearerToken()) {
                try {
                    $user->currentAccessToken()->delete();
                } catch (\Exception $e) {
                    Log::error('Failed to delete current access token: ' . $e->getMessage());
                }
            }

            // Clear any session data related to two-factor auth
            if (session()->has('2fa')) {
                session()->forget('2fa');
            }
        }
    }
}
