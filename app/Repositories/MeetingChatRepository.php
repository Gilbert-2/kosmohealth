<?php

namespace App\Repositories;

use App\Helpers\CalHelper;
use App\Http\Resources\ChatRoom as ChatRoomResources;
use App\Models\ChatRoom;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeetingChatRepository
{
    protected $meeting;
    protected $chatRoom;

    /**
     * Instantiate a new instance
     * @return void
     */
    public function __construct(
        Meeting $meeting,
        ChatRoom $chatRoom
    ) {
        $this->meeting = $meeting;
        $this->chatRoom = $chatRoom;
    }

    /**
     * Find meeting with given uuid or throw an error
     * @param uuid $uuid
     */
    public function findByUuidOrFail(Meeting $meeting, $uuid, $field = 'message') : ChatRoom
    {
        $chatRoom = $this->chatRoom->whereId($meeting->getMeta('chat_room_id'))->filterByUuid($uuid)->first();

        if (! $chatRoom) {
            throw ValidationException::withMessages([$field => __('global.could_not_find', ['attribute' => __('chat.chat_room')])]);
        }

        return $chatRoom;
    }

    /**
     * Find chat room
     */
    public function find(Meeting $meeting) : ChatRoomResources
    {
        return new ChatRoomResources($this->chatRoom->whereId($meeting->getMeta('chat_room_id'))->first());
    }

    public function create(Request $request, Meeting $meeting) : ChatRoom
    {
        if ($this->chatRoom->whereId($meeting->getMeta('chat_room_id'))->count()) {
            throw ValidationException::withMessages(['message' => trans('user.permission_denied')]);
        }

        $chatRoom = $this->chatRoom->forceCreate([
            'name' => $meeting->title ?? trans('meeting.meeting') . ' @ ' . CalHelper::displayDateTime($meeting->start_date_time),
        ]);

        $meta = $meeting->meta;
        $meta['chat_room_id'] = $chatRoom->id;
        $meeting->meta = $meta;
        $meeting->save();

        return $chatRoom;
    }

    public function delete(Meeting $meeting, ChatRoom $chatRoom)
    {
        $chatRoom->delete();
    }
}
