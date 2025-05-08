<?php

namespace App\Traits;

use Twilio\Rest\Client;

trait SendTwilioMessage
{
    public function sendMessage($to, $message)
    {
        if (! $to) {
            return;
        }

        if (! config('config.sms.twilio_sid') || ! config('config.sms.twilio_auth_token')) {
            return;
        }

        $client = new Client(config('config.sms.twilio_sid'), config('config.sms.twilio_auth_token'));
        try {
            $client->messages->create($to, ['from' => config('config.sms.twilio_number'), 'body' => $message]);
        } catch (\Exception $e) {
            //
        }
    }
}
