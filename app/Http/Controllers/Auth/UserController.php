<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\AuthUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Repositories\Auth\UserRepository;
use App\Http\Resources\User as UserResource;

class UserController extends Controller
{
    protected $repo;

    /**
     * Instantiate a new instance
     * @return void
     */
    public function __construct(
        UserRepository $repo
    ) {
        $this->repo = $repo;
    }

    /**
     * Authenticated user
     * @get ("/api/auth/user")
     * @return array
     */
    public function me()
    {
        $user = \Auth::user();
        
        if (!$user) {
            \Log::warning('Auth user endpoint called without authentication');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        \Log::info('Auth user endpoint accessed', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')
        ]);
        
        return new AuthUser($user);
    }

    /**
     * Update authenticated user profile
     * @post ("/api/auth/profile")
     * @return array
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $this->repo->updateProfile(auth()->user());

        return $this->success([
            'message' => __('global.updated', ['attribute' => __('user.profile')]),
            'user' => new AuthUser($user)
        ]);
    }

    /**
     * Store user preference
     * @post ("/api/user/preference")
     * @return array
     */
    public function preference()
    {
        $this->repo->preference();

        return $this->success(['message' => __('global.updated', ['attribute' => __('user.user_preference')])]);
    }

    /**
     * Get user pre requisite
     * @get ("/api/users/pre-requisite")
     * @return array
     */
    public function preRequisite()
    {
        return $this->ok($this->repo->getPreRequisite());
    }

    /**
     * Get all users
     * @get ("/api/users")
     * @return array
     */
    public function index()
    {
        $this->authorize('view', User::class);

        return $this->repo->paginate();
    }

    /**
     * Create user
     * @post ("/api/users")
     * @param ({
     *      @Parameter("name", type="string", required="true", description="User name"),
     *      @Parameter("email", type="email", required="true", description="User email"),
     *      @Parameter("username", type="string", required="true", description="User username"),
     *      @Parameter("password", type="string", required="true", description="User password"),
     *      @Parameter("confirm_password", type="string", required="optional", description="User confirm password"),
     * })
     * @return array
     */
    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = $this->repo->create();

        $user = new UserResource($user);

        return $this->success(['message' => __('global.added', ['attribute' => __('user.user')]), 'user' => $user]);
    }

    /**
     * Get user detail
     * @get ("/api/users/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="User unique id"),
     * })
     * @return UserResource
     */
    public function show(User $user)
    {
        $this->authorize('show', $user);

        $user->load('roles');

        return new UserResource($user);
    }



    /**
     * Update user
     * @patch ("/api/users/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="User unique id"),
     *      @Parameter("name", type="string", required="true", description="User name"),
     *      @Parameter("email", type="email", required="true", description="User email"),
     *      @Parameter("username", type="string", required="true", description="User username"),
     *      @Parameter("password", type="string", required="true", description="User password"),
     *      @Parameter("confirm_password", type="string", required="optional", description="User confirm password"),
     * })
     * @return array
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user = $this->repo->update($user);

        return $this->success(['message' => __('global.updated', ['attribute' => __('user.user')])]);
    }

    /**
     * Get user subscriptions
     * @get ("/api/users/subscriptions")
     * @return array
     */
    public function getSubscriptions()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            // Log for debugging
            \Log::info('User accessing subscriptions', [
                'user_id' => $user->id,
                'user_roles' => $user->roles->pluck('name'),
                'user_permissions' => $user->getAllPermissions()->pluck('name')
            ]);
            
            // Get user's subscription data - handle meta field safely
            $meta = $user->meta ?? [];
            $subscriptions = [
                'has_active_subscription' => $meta['is_premium'] ?? false,
                'subscription_type' => $meta['subscription_type'] ?? 'free',
                'subscription_status' => $meta['subscription_status'] ?? 'inactive',
                'subscription_start_date' => $meta['subscription_start_date'] ?? null,
                'subscription_end_date' => $meta['subscription_end_date'] ?? null,
                'features' => $this->getSubscriptionFeatures($user)
            ];

            return $this->success($subscriptions);
        } catch (\Exception $e) {
            \Log::error('Subscription error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to retrieve subscription data');
        }
    }

    /**
     * Get subscription features based on user's subscription
     */
    private function getSubscriptionFeatures($user)
    {
        $meta = $user->meta ?? [];
        $isPremium = $meta['is_premium'] ?? false;
        
        if ($isPremium) {
            return [
                'premium_support' => true,
                'advanced_analytics' => true,
                'priority_meetings' => true,
                'custom_branding' => true,
                'unlimited_storage' => true
            ];
        }
        
        return [
            'premium_support' => false,
            'advanced_analytics' => false,
            'priority_meetings' => false,
            'custom_branding' => false,
            'unlimited_storage' => false
        ];
    }

    /**
     * Update user subscription
     * @post ("/api/users/subscriptions")
     * @param ({
     * })
     * @return array
     */
    public function updateSubscription()
    {
        $user = $this->repo->updateSubscription();

        return $this->success([]);
    }

    /**
     * Delete user subscription
     * @post ("/api/users/subscriptions/delete")
     * @param ({
     * })
     * @return array
     */
    public function deleteSubscription()
    {
        $user = $this->repo->deleteSubscription();

        return $this->success([]);
    }

    /**
     * Update user status
     * @post ("/api/users/{uuid}/status")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="User unique id"),
     *      @Parameter("status", type="string", required="true", description="User status"),
     * })
     * @return array
     */
    public function updateStatus(User $user)
    {
        $this->authorize('update', $user);

        $user = $this->repo->updateStatus($user);

        return $this->success(['message' => __('global.updated', ['attribute' => __('user.user')])]);
    }

    /**
     * Update user premiumship
     * @post ("/api/users/{uuid}/premium")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="User unique id"),
     * })
     * @return array
     */
    public function premium(User $user)
    {
        $this->authorize('update', $user);

        $user = $this->repo->premium($user);

        return $this->success(['message' => __('global.updated', ['attribute' => __('user.user')])]);
    }

    /**
     * Update user role
     * @post ("/api/users/{uuid}/role")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="User unique id"),
     *      @Parameter("role", type="string", required="true", description="Role name"),
     * })
     * @return array
     */
    public function updateRole(User $user)
    {
        // Allow users to update their own role or admin to update any role
        if ($user->id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => __('user.permission_denied')], 403);
        }

        $role = request('role');
        if (!$role) {
            return response()->json(['message' => 'Role is required'], 422);
        }

        // Validate role exists
        $roleModel = \Spatie\Permission\Models\Role::where('name', $role)->first();
        if (!$roleModel) {
            return response()->json(['message' => 'Invalid role'], 422);
        }

        // Remove current roles and assign new role
        $user->syncRoles([$role]);

        return $this->success([
            'message' => __('global.updated', ['attribute' => __('user.role')]),
            'user' => new AuthUser($user->fresh())
        ]);
    }

    /**
     * Update authenticated user role
     * @post ("/api/auth/update-role")
     * @param ({
     *      @Parameter("role", type="string", required="true", description="Role name"),
     * })
     * @return array
     */
    public function updateAuthUserRole()
    {
        $user = auth()->user();
        $role = request('role');
        
        if (!$role) {
            return response()->json(['message' => 'Role is required'], 422);
        }

        // Validate role exists
        $roleModel = \Spatie\Permission\Models\Role::where('name', $role)->first();
        if (!$roleModel) {
            return response()->json(['message' => 'Invalid role'], 422);
        }

        // Remove current roles and assign new role
        $user->syncRoles([$role]);

        return $this->success([
            'message' => __('global.updated', ['attribute' => __('user.role')]),
            'user' => new AuthUser($user->fresh())
        ]);
    }

    /**
     * Delete user
     * @delete ("/api/users/{uuid}")
     * @param ({
     *      @Parameter("uuid", type="uuid", required="true", description="User unique id"),
     * })
     * @return array
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $this->repo->delete($user);

        return $this->success(['message' => __('global.deleted', ['attribute' => __('user.user')])]);
    }
}
