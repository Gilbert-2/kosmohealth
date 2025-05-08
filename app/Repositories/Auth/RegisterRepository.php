<?php

namespace App\Repositories\Auth;

use App\Enums\Auth\UserStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\UserEmailVerification;
use App\Notifications\UserRegistered;
use Illuminate\Validation\ValidationException;

class RegisterRepository
{
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
     * Register user
     *
     * @param array $userData
     * @return User
     */
    public function register(array $userData): User
    {
        $this->checkRegistrationEnabled();

        DB::beginTransaction();

        try {
            // Create the user
            $user = $this->createUser($userData);

            // Set user status based on configuration
            $status = $this->determineUserStatus();
            $user->status = $status;

            // Set activation token
            $user->meta = ['activation_token' => Str::uuid()];
            $user->save();

            // Assign default role
            $user->assignRole('user');

            DB::commit();

            // Send verification email if required
            $this->sendVerificationEmailIfNeeded($user);

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verify user account
     *
     * @param array $data
     * @return void
     */
    public function verify(array $data): void
    {
        $token = $data['token'];
        $user = $this->findUserByActivationToken($token);

        $this->validateUserForVerification($user);

        // Update user status
        $user->email_verified_at = now();
        $user->status = config('config.auth.account_approval') ?
            UserStatus::PENDING_APPROVAL :
            UserStatus::ACTIVATED;
        $user->save();

        // Send welcome notification
        $user->notify(new UserRegistered($user));
    }

    /**
     * Check if registration is enabled
     *
     * @return void
     */
    protected function checkRegistrationEnabled(): void
    {
        if (!config('config.auth.registration')) {
            throw ValidationException::withMessages(['message' => trans('general.feature_not_available')]);
        }
    }

    /**
     * Create a new user
     *
     * @param array $userData
     * @return User
     */
    protected function createUser(array $userData): User
    {
        return $this->user->create([
            'name'     => $userData['name'],
            'email'    => $userData['email'],
            'username' => $userData['username'],
            'mobile'   => $userData['mobile'] ?? null,
            'password' => Hash::make($userData['password']),
        ]);
    }

    /**
     * Determine user status based on configuration
     *
     * @return string
     */
    protected function determineUserStatus(): string
    {
        if (config('config.auth.email_verification')) {
            return UserStatus::PENDING_ACTIVATION;
        } else if (config('config.auth.account_approval')) {
            return UserStatus::PENDING_APPROVAL;
        } else {
            return UserStatus::ACTIVATED;
        }
    }

    /**
     * Send verification email if needed
     *
     * @param User $user
     * @return void
     */
    protected function sendVerificationEmailIfNeeded(User $user): void
    {
        if (config('config.auth.email_verification')) {
            $user->notify(new UserEmailVerification($user));
        }
    }

    /**
     * Find user by activation token
     *
     * @param string $token
     * @return User
     */
    protected function findUserByActivationToken(string $token): User
    {
        $user = $this->user->where('meta->activation_token', $token)->first();

        if (!$user) {
            throw ValidationException::withMessages(['message' => __('auth.register.invalid_activation_token')]);
        }

        return $user;
    }

    /**
     * Validate user for verification
     *
     * @param User $user
     * @return void
     */
    protected function validateUserForVerification(User $user): void
    {
        if ($user->status != UserStatus::PENDING_ACTIVATION) {
            throw ValidationException::withMessages(['message' => __('general.invalid_action')]);
        }
    }
}
