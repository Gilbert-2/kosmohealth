<?php

namespace App\Observers;

use App\Models\ChatRoomMember;
use App\Models\Invitee;
use Illuminate\Support\Arr;

class InviteeObserver
{
    /**
     * Handle the invitee "created" event.
     *
     * @param  \App\Models\Invitee  $invitee
     * @return void
     */
    public function created(Invitee $invitee)
    {
        $meeting = $invitee->meeting;

        if (! Arr::get($meeting->meta, 'config.auto_create_chat_room', config('config.meeting.auto_create_chat_room'))) {
            return;
        }

        $chat_room_id = $meeting->getMeta('chat_room_id');
        $user_id = optional($invitee->contact)->user_id;

        if (! $user_id) {
            return;
        }

        $chatRoomMember = ChatRoomMember::firstOrCreate([
            'chat_room_id' => $chat_room_id,
            'user_id' => $user_id,
        ]);

        $chatRoomMember->joined_at = $chatRoomMember->joined_at ?? now();

        if ($chatRoomMember->user_id == $meeting->user_id) {
            $chatRoomMember->is_owner = 1;
        }

        $chatRoomMember->save();
    }

    /**
     * Handle the invitee "deleted" event.
     *
     * @param  \App\Models\Invitee  $invitee
     * @return void
     */
    public function deleted(Invitee $invitee)
    {
        $chat_room_id = $invitee->meeting->getMeta('chat_room_id');
        $user_id = optional($invitee->contact)->user_id;

        ChatRoomMember::whereChatRoomId($chat_room_id)
            ->whereUserId($user_id)
            ->delete();
    }
}
