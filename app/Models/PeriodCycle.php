<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PeriodCycle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'cycle_length',
        'flow_intensity',
        'mood',
        'notes',
        'is_predicted'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cycle_length' => 'decimal:2',
        'is_predicted' => 'boolean'
    ];

    /**
     * Get the user that owns the period cycle
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the symptoms for this cycle
     */
    public function symptoms(): HasMany
    {
        return $this->hasMany(PeriodSymptom::class, 'cycle_id');
    }

    /**
     * Get cycle length in days
     */
    public function getCycleLengthAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if cycle is current/active
     */
    public function getIsCurrentAttribute(): bool
    {
        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Get days since cycle started
     */
    public function getDaysSinceStartAttribute(): int
    {
        return $this->start_date->diffInDays(Carbon::today());
    }

    /**
     * Scope for user's cycles
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for actual (non-predicted) cycles
     */
    public function scopeActual($query)
    {
        return $query->where('is_predicted', false);
    }

    /**
     * Scope for predicted cycles
     */
    public function scopePredicted($query)
    {
        return $query->where('is_predicted', true);
    }

    /**
     * Scope for cycles within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }
}