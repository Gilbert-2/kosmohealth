<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PregnancySymptom extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pregnancy_record_id',
        'user_id',
        'symptom_date',
        'symptom_type',
        'severity',
        'description',
        'symptom_data',
        'requires_medical_attention',
        'medical_notes'
    ];

    protected $casts = [
        'symptom_date' => 'date',
        'symptom_data' => 'array',
        'requires_medical_attention' => 'boolean'
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
        return $query->where('symptom_type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeRequiresAttention($query)
    {
        return $query->where('requires_medical_attention', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('symptom_date', [$startDate, $endDate]);
    }
} 