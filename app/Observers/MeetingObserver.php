<?php

namespace App\Observers;

use App\Helpers\CalHelper;
use App\Models\Chat;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\Meeting;
use Illuminate\Support\Arr;

class MeetingObserver
{
    /**
     * Handle the meeting "created" event.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return void
     */
    public function created(Meeting $meeting)
    {
        if (! Arr::get($meeting->meta, 'config.auto_create_chat_room', config('config.meeting.auto_create_chat_room'))) {
            return;
        }

        $name = ! empty($meeting->title) ? $meeting->title : (trans('meeting.meeting') . ' @ ' . CalHelper::displayDateTime($meeting->start_date_time));

        $chat_room = ChatRoom::whereName($name)->first();

        if ($chat_room) {
            return;
        }

        $chat_room = ChatRoom::forceCreate([
            'name' => $name,
            'meta' => ['meeting_uuid' => $meeting->uuid]
        ]);

        $meta = $meeting->meta;
        $meta['chat_room_id'] = $chat_room->id;
        $meeting->meta = $meta;
        $meeting->save();

        $chatRoomMember = ChatRoomMember::firstOrCreate([
            'chat_room_id' => $chat_room->id,
            'user_id' => $meeting->user_id,
        ]);

        $chatRoomMember->joined_at = $chatRoomMember->joined_at ?? now();
        $chatRoomMember->is_owner = 1;
        $chatRoomMember->save();

        $chat = Chat::forceCreate([
            'chat_room_id' => $chat_room->id,
            'message' => 'Meeting created',
            'user_id' => $meeting->user_id,
        ]);
    }

    /**
     * Handle the meeting "deleting" event.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return void
     */
    public function deleting(Meeting $meeting)
    {
        $chat_room_id = $meeting->getMeta('chat_room_id');
        ChatRoom::whereId($chat_room_id)->delete();
    }
}
