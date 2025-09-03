<?php

namespace App\Http\Controllers;

use App\Models\Whiteboard;
use App\Models\User;
use Illuminate\Http\Request;

class WhiteboardController extends Controller
{
    /**
     * Show the whiteboard
     *
     * @param Whiteboard $whiteboard
     * @return \Illuminate\View\View
     */
    public function show(Whiteboard $whiteboard)
    {
        $this->authorize('view', $whiteboard);
        
        return view('whiteboard', compact('whiteboard'));
    }

    /**
     * Show the whiteboard sharing form
     *
     * @param Whiteboard $whiteboard
     * @return \Illuminate\View\View
     */
    public function showShareForm(Whiteboard $whiteboard)
    {
        // Use the policy to check permissions
        $this->authorize('share', $whiteboard);
        
        $users = User::where('id', '!=', auth()->id())->get();
        $sharedUsers = $whiteboard->sharedUsers;
        
        return view('whiteboards.share', compact('whiteboard', 'users', 'sharedUsers'));
    }

    /**
     * Process whiteboard sharing
     *
     * @param Request $request
     * @param Whiteboard $whiteboard
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processShare(Request $request, Whiteboard $whiteboard)
    {
        // Use the policy to check permissions
        $this->authorize('share', $whiteboard);
        
        $validated = $request->validate([
            'users' => 'array',
            'users.*' => 'exists:users,id'
        ]);
        
        // Sync the shared users (empty array if no users selected)
        $whiteboard->sharedUsers()->sync($validated['users'] ?? []);
        
        return redirect()->route('whiteboards.show', $whiteboard)
            ->with('success', 'Whiteboard shared successfully');
    }
}


