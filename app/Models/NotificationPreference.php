<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_reminders',
        'ovulation_alerts',
        'appointment_reminders',
        'community_updates',
        'admin_announcements',
        'system_alerts',
        'quiet_from',
        'quiet_to',
        'metadata',
    ];

    protected $casts = [
        'period_reminders' => 'boolean',
        'ovulation_alerts' => 'boolean',
        'appointment_reminders' => 'boolean',
        'community_updates' => 'boolean',
        'admin_announcements' => 'boolean',
        'system_alerts' => 'boolean',
        'quiet_from' => 'datetime:H:i',
        'quiet_to' => 'datetime:H:i',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


