<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Whiteboard;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WhiteboardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the whiteboard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Whiteboard  $whiteboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Whiteboard $whiteboard)
    {
        // Owner can always view
        if ($user->id === $whiteboard->user_id) {
            return Response::allow();
        }
        
        // Check if whiteboard is shared with this user
        if ($whiteboard->sharedUsers && $whiteboard->sharedUsers->contains($user->id)) {
            return Response::allow();
        }
        
        // Users with whiteboard-share permission can view any whiteboard
        if ($user->can('whiteboard-share')) {
            return Response::allow();
        }
        
        return Response::deny('You do not have permission to view this whiteboard.', 403);
    }

    /**
     * Determine whether the user can share the whiteboard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Whiteboard  $whiteboard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function share(User $user, Whiteboard $whiteboard)
    {
        // Owner can always share
        if ($user->id === $whiteboard->user_id) {
            return Response::allow();
        }
        
        // Users with explicit whiteboard-share permission can share any whiteboard
        if ($user->hasPermissionTo('whiteboard-share')) {
            return Response::allow();
        }
        
        // Log the failure for debugging
        \Log::debug('Share permission denied', [
            'user_id' => $user->id,
            'whiteboard_id' => $whiteboard->id,
            'whiteboard_owner' => $whiteboard->user_id,
            'has_permission' => $user->hasPermissionTo('whiteboard-share')
        ]);
        
        return Response::deny('You do not have permission to share this whiteboard.', 403);
    }
}

