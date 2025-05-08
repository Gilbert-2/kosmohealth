<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginActivity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'device_name',
        'meta'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'meta' => 'array'
    ];

    /**
     * Get the user that owns the login activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session duration in seconds.
     *
     * @return int|null
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->logout_at) {
            return null;
        }

        return $this->login_at->diffInSeconds($this->logout_at);
    }

    /**
     * Format the duration as a human-readable string.
     *
     * @return string|null
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->logout_at) {
            return null;
        }

        return $this->login_at->diffForHumans($this->logout_at, true);
    }

    /**
     * Check if the session is still active.
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->logout_at === null;
    }
}
