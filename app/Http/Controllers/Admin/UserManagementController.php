<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PeriodCycle;
use App\Models\PregnancyRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    /**
     * Get users with health data summary
     * GET /api/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            $query = User::with(['roles'])
                ->withCount(['periodCycles', 'pregnancyRecords'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            if ($request->has('has_health_data')) {
                if ($request->boolean('has_health_data')) {
                    $query->where(function($q) {
                        $q->has('periodCycles')->orHas('pregnancyRecords');
                    });
                } else {
                    $query->whereDoesntHave('periodCycles')
                          ->whereDoesntHave('pregnancyRecords');
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($request->get('per_page', 15));

            // Add health data summary to each user
            $users->getCollection()->transform(function($user) {
                $user->health_summary = [
                    'has_period_data' => $user->period_cycles_count > 0,
                    'has_pregnancy_data' => $user->pregnancy_records_count > 0,
                    'last_period_cycle' => $user->periodCycles()->latest()->first()?->start_date,
                    'active_pregnancy' => $user->pregnancyRecords()->where('status', 'active')->exists()
                ];
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin get users error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load users'
            ], 500);
        }
    }

    /**
     * Get user details with health data
     * GET /api/admin/users/{id}
     */
    public function show($userId): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            // Find user by UUID or ID
            $user = User::where('uuid', $userId)->orWhere('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $user->load(['roles', 'periodCycles' => function($q) {
                $q->latest()->limit(5);
            }, 'pregnancyRecords' => function($q) {
                $q->latest()->limit(3);
            }]);

            $healthSummary = [
                'period_tracking' => [
                    'total_cycles' => $user->periodCycles()->count(),
                    'last_cycle' => $user->periodCycles()->latest()->first(),
                    'avg_cycle_length' => $user->periodCycles()->whereNotNull('cycle_length')->avg('cycle_length')
                ],
                'pregnancy_tracking' => [
                    'total_pregnancies' => $user->pregnancyRecords()->count(),
                    'active_pregnancy' => $user->pregnancyRecords()->where('status', 'active')->first(),
                    'completed_pregnancies' => $user->pregnancyRecords()->where('status', 'completed')->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'health_summary' => $healthSummary
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin get user details error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load user details'
            ], 500);
        }
    }

    /**
     * Update user role
     * PUT /api/admin/users/{id}/role
     */
    public function updateRole(Request $request, $userId): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            $request->validate([
                'role' => 'required|string|exists:roles,name'
            ]);

            // Find user by UUID or ID
            $user = User::where('uuid', $userId)->orWhere('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            $user->syncRoles([$request->role]);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'new_role' => $request->role
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role'
            ], 500);
        }
    }

    /**
     * Update user status (activate/deactivate)
     * PUT /api/admin/users/{id}/status
     */
    // NOTE: Original updateStatus is defined above. This duplicate will be removed to avoid redeclare errors.

    /**
     * Create new user
     * POST /api/admin/users
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:6',
                'role' => 'required|string|exists:roles,name',
                'birth_date' => 'nullable|date',
                'mobile' => 'nullable|string'
            ]);
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'birth_date' => $request->birth_date,
                'mobile' => $request->mobile,
                'status' => 'activated'
            ]);

            $user->assignRole($request->role);

            // Ensure login permission exists and grant to new user so they can sign in
            try {
                $perm = Permission::findOrCreate('enable-login');
                $user->givePermissionTo($perm);
            } catch (\Throwable $e) {
                // ignore if permission system not ready; admin can assign later
            }

            // Send welcome/activation email from configured sender
            try {
                Mail::raw('Your KosmoHealth account has been created and activated. You can now sign in.', function ($m) use ($user) {
                    $m->from(config('mail.from.address'), config('mail.from.name'))
                      ->to($user->email, $user->name)
                      ->subject('Welcome to KosmoHealth');
                });
            } catch (\Throwable $e) {
                \Log::warning('Welcome email failed', ['user_id'=>$user->id,'error'=>$e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load('roles')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user'
            ], 500);
        }
    }

    /**
     * After updating status to activated, send activation email
     */
    public function updateStatus(Request $request, $userId): JsonResponse
    {
        try {
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            $request->validate([
                'status' => 'required|in:activated,deactivated,suspended'
            ]);

            $user = User::where('uuid', $userId)->orWhere('id', $userId)->first();
            if (!$user) {
                return response()->json(['success'=>false,'message'=>'User not found'],404);
            }

            $user->update(['status' => $request->status]);

            // When activating, ensure login permission and send activation email
            if ($request->status === 'activated') {
                try {
                    $perm = \Spatie\Permission\Models\Permission::findOrCreate('enable-login');
                    $user->givePermissionTo($perm);
                } catch (\Throwable $e) {
                    \Log::warning('Grant enable-login failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }

                try {
                    \Illuminate\Support\Facades\Mail::raw('Your KosmoHealth account has been activated. You can now sign in.', function ($m) use ($user) {
                        $m->from(config('mail.from.address'), config('mail.from.name'))
                          ->to($user->email, $user->name)
                          ->subject('Your account has been activated');
                    });
                } catch (\Throwable $e) {
                    \Log::warning('Activation email failed', ['user_id'=>$user->id,'error'=>$e->getMessage()]);
                }
            }

            if ($request->status === 'activated') {
                try {
                    Mail::raw('Your KosmoHealth account has been activated. You can now sign in.', function ($m) use ($user) {
                        $m->to($user->email, $user->name)->subject('Your account has been activated');
                    });
                } catch (\Throwable $e) {
                    \Log::warning('Activation email failed', ['user_id'=>$user->id,'error'=>$e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [ 'user_id' => $user->id, 'new_status' => $request->status ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status'
            ], 500);
        }
    }

    /**
     * Get available roles
     * GET /api/admin/users/roles
     */
    public function getRoles(): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }
            $roles = Role::all(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load roles'
            ], 500);
        }
    }

    /**
     * Get user statistics
     * GET /api/admin/users/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }
            $stats = [
                'total_users' => User::count(),
                'users_by_role' => User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->selectRaw('roles.name as role, COUNT(*) as count')
                    ->groupBy('roles.name')
                    ->get(),
                'users_with_health_data' => User::whereHas('periodCycles')
                    ->orWhereHas('pregnancyRecords')
                    ->count(),
                'active_pregnancies' => User::whereHas('pregnancyRecords', function($q) {
                    $q->where('status', 'active');
                })->count(),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user statistics'
            ], 500);
        }
    }

    /**
     * Delete user
     * DELETE /api/admin/users/{id}
     */
    public function destroy($userId): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$this->isAdmin(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin access required'
                ], 403);
            }

            // Find user by UUID or ID
            $user = User::where('uuid', $userId)->orWhere('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent admin from deleting themselves
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }

            // Store user info for logging
            $userInfo = [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email
            ];

            // Delete user (this will cascade delete related data)
            $user->delete();

            \Log::warning('Admin deleted user', [
                'deleted_user' => $userInfo,
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin delete user error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user'
            ], 500);
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check role field
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        // Check if user has admin permission (if using Spatie permissions)
        if (method_exists($user, 'can') && $user->can('admin-access')) {
            return true;
        }

        // Check if user has admin role (if using Spatie roles)
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Check if user is in admin table or has admin flag
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return false;
    }
}
