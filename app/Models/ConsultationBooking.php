<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason_key',
        'preferred_datetime',
        'duration_minutes',
        'notes',
        'status',
        'assigned_host_id',
        'meeting_id',
    ];

    protected $casts = [
        // Serialize with timezone to ensure consistent parsing on frontend
        'preferred_datetime' => 'datetime:Y-m-d\TH:i:sP',
    ];

    protected $appends = [
        'preferred_datetime_local',
        'preferred_datetime_iso',
        'meeting_uuid',
    ];

    // Mutators
    public function setPreferredDatetimeAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['preferred_datetime'] = null;
            return;
        }
        try {
            $dt = \Carbon\Carbon::parse($value)->utc();
            // Store normalized in UTC to avoid timezone shifts later
            $this->attributes['preferred_datetime'] = $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            // Fallback: set raw value
            $this->attributes['preferred_datetime'] = $value;
        }
    }

    // Accessors
    public function getPreferredDatetimeLocalAttribute(): ?string
    {
        if (empty($this->preferred_datetime)) {
            return null;
        }
        try {
            // Return a human-stable string without timezone conversion for UI display
            return \Carbon\Carbon::parse($this->preferred_datetime)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $this->preferred_datetime;
        }
    }

    public function getPreferredDatetimeIsoAttribute(): ?string
    {
        if (empty($this->preferred_datetime)) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($this->preferred_datetime)->utc()->toIso8601String();
        } catch (\Throwable $e) {
            return (string) $this->preferred_datetime;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'assigned_host_id');
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    // Scopes
    public function scopeAssignedToHost($query, int $hostId)
    {
        return $query->where('assigned_host_id', $hostId);
    }

    // Computed attributes
    public function getMeetingUuidAttribute(): ?string
    {
        return $this->relationLoaded('meeting')
            ? optional($this->meeting)->uuid
            : optional($this->meeting()->select('id','uuid')->first())->uuid;
    }
}
