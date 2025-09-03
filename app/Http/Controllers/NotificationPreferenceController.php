<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $prefs = NotificationPreference::firstOrCreate(['user_id' => $user->id]);
        return $this->ok($prefs);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'period_reminders' => 'sometimes|boolean',
            'ovulation_alerts' => 'sometimes|boolean',
            'appointment_reminders' => 'sometimes|boolean',
            'community_updates' => 'sometimes|boolean',
            'admin_announcements' => 'sometimes|boolean',
            'system_alerts' => 'sometimes|boolean',
            'quiet_from' => 'sometimes|nullable|date_format:H:i',
            'quiet_to' => 'sometimes|nullable|date_format:H:i',
            'metadata' => 'sometimes|array'
        ]);

        $prefs = NotificationPreference::firstOrCreate(['user_id' => $user->id]);
        $prefs->fill($data);
        $prefs->save();

        return $this->success(['message' => 'Preferences updated', 'data' => $prefs]);
    }
}


