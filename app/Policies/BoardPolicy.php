/**
 * Determine whether the user can share the board.
 *
 * @param  \App\Models\User  $user
 * @param  \App\Models\Board  $board
 * @return bool
 */
public function share(User $user, Board $board)
{
    // Owner can always share
    if ($user->id === $board->user_id) {
        return true;
    }
    
    // Users with explicit board-share permission can share any board
    if ($user->can('board-share')) {
        return true;
    }
    
    return false;
}