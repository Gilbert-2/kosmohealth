<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PregnancyRiskFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pregnancy_record_id',
        'user_id',
        'risk_factor_type',
        'risk_factor_name',
        'risk_level',
        'description',
        'risk_data',
        'is_managed',
        'management_plan',
        'identified_date',
        'resolved_date'
    ];

    protected $casts = [
        'risk_data' => 'array',
        'is_managed' => 'boolean',
        'identified_date' => 'date',
        'resolved_date' => 'date'
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
    public function scopeByType($query, $type)
    {
        return $query->where('risk_factor_type', $type);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeActive($query)
    {
        return $query->where('is_managed', false);
    }

    public function scopeManaged($query)
    {
        return $query->where('is_managed', true);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('identified_date', [$startDate, $endDate]);
    }

    // Helper methods
    public function isResolved()
    {
        return $this->resolved_date !== null;
    }

    public function getDaysSinceIdentified()
    {
        return $this->identified_date->diffInDays(now());
    }

    public function getDaysToResolution()
    {
        if ($this->resolved_date) {
            return $this->identified_date->diffInDays($this->resolved_date);
        }
        return null;
    }
} 