<?php

namespace App\Console\Commands;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingReminder as MeetingReminderNotification;
use Illuminate\Console\Command;

class MeetingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meeting:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Meeting reminder';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $meetings = Meeting::whereDay('start_date_time', today()->format('d'))
            ->whereMonth('start_date_time', today()->format('m'))
            ->whereYear('start_date_time', today()->format('Y'))
            ->whereId(28)
            ->get();

        foreach ($meetings as $meeting) {

            if ($meeting->getMeta('status') != MeetingStatus::SCHEDULED) {
                continue;
            }

            if (! $meeting->getMeta('should_remind')) {
                continue;
            }

            if ($meeting->getMeta('remind_sent')) {
                continue;
            }

            $remind_before = (int) $meeting->getMeta('remind_before');

            $remind_time = $meeting->start_date_time->subMinutes($remind_before);

            if (now() < $remind_time) {
                continue;
            }

            if (now() > $meeting->start_date_time) {
                continue;
            }

            $meeting->load('invitees', 'invitees.contact');

            foreach ($meeting->invitees as $invitee) {
                (new User)->forceFill([
                    'email' => $invitee->contact->email,
                ])->notify(new MeetingReminderNotification($meeting));
            }

            $meta = $meeting->meta;
            $meta['remind_sent'] = true;
            $meeting->meta = $meta;
            $meeting->save();
        }
    }
}
