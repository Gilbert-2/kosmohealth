<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatRoom as ChatRoomResource;
use App\Models\Meeting;
use App\Repositories\MeetingChatRepository;
use Illuminate\Http\Request;

class MeetingChatController extends Controller
{
    protected $repo;

    public function __construct(
        MeetingChatRepository $repo
    ) {
        $this->repo = $repo;
    }

    public function index(Meeting $meeting)
    {
        $this->authorize('list', $meeting);

        return $this->ok($this->repo->find($meeting));
    }

    public function store(Request $request, Meeting $meeting)
    {
        $meeting->isAccessible(true);

        $meeting->ensureIsScheduledOrLive();

        $chatRoom = $this->repo->create($request, $meeting);

        $chatRoom = new ChatRoomResource($meeting);

        return $this->success(['message' => __('global.created', ['attribute' => __('chat.chat_room')]), 'chat_room' => $chatRoom]);
    }

    public function show(Meeting $meeting, $chat)
    {
        $this->authorize('list', $meeting);

        $chatRoom = $this->repo->findByUuidOrFail($meeting, $chat);

        return new ChatRoomResource($chatRoom);
    }

    public function destroy(Meeting $meeting, $chat)
    {
        $meeting->isAccessible(true);

        $chatRoom = $this->repo->findByUuidOrFail($meeting, $chat);

        $this->repo->delete($meeting, $chatRoom);

        return $this->success(['message' => __('global.deleted', ['attribute' => __('chat.chat_room')])]);
    }
}
