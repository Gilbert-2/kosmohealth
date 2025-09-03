<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BabyDevelopmentMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pregnancy_record_id',
        'user_id',
        'week_number',
        'milestone_title',
        'milestone_description',
        'baby_size',
        'baby_length_cm',
        'baby_weight_grams',
        'development_details',
        'organ_development',
        'movement_patterns',
        'is_completed',
        'completed_date',
        'notes'
    ];

    protected $casts = [
        'development_details' => 'array',
        'organ_development' => 'array',
        'movement_patterns' => 'array',
        'is_completed' => 'boolean',
        'completed_date' => 'date',
        'baby_length_cm' => 'decimal:2',
        'baby_weight_grams' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relationships
    public function pregnancyRecord()
    {
        return $this->belongsTo(PregnancyRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByWeek($query, $week)
    {
        return $query->where('week_number', $week);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeUpToWeek($query, $week)
    {
        return $query->where('week_number', '<=', $week);
    }

    public function scopeFromWeek($query, $week)
    {
        return $query->where('week_number', '>=', $week);
    }

    public function scopeByWeekRange($query, $startWeek, $endWeek)
    {
        return $query->whereBetween('week_number', [$startWeek, $endWeek]);
    }
} 