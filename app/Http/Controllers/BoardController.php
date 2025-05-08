/**
 * Show the board sharing form
 *
 * @param Board $board
 * @return \Illuminate\View\View
 */
public function showShareForm(Board $board)
{
    $this->authorize('share', $board);
    
    $users = User::where('id', '!=', auth()->id())->get();
    $sharedUsers = $board->sharedUsers;
    
    return view('boards.share', compact('board', 'users', 'sharedUsers'));
}

/**
 * Process board sharing
 *
 * @param Request $request
 * @param Board $board
 * @return \Illuminate\Http\RedirectResponse
 */
public function processShare(Request $request, Board $board)
{
    $this->authorize('share', $board);
    
    $validated = $request->validate([
        'users' => 'required|array',
        'users.*' => 'exists:users,id'
    ]);
    
    // Sync the shared users
    $board->sharedUsers()->sync($validated['users']);
    
    return redirect()->route('boards.show', $board)
        ->with('success', 'Board shared successfully');
}

/**
 * Remove a user from board sharing
 *
 * @param Board $board
 * @param User $user
 * @return \Illuminate\Http\RedirectResponse
 */
public function removeShare(Board $board, User $user)
{
    $this->authorize('share', $board);
    
    $board->sharedUsers()->detach($user->id);
    
    return redirect()->route('boards.share.form', $board)
        ->with('success', 'User removed from board sharing');
}