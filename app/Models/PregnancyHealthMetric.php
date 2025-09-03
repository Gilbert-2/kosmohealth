<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PregnancyHealthMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pregnancy_record_id',
        'user_id',
        'measurement_date',
        'weight_kg',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'blood_sugar',
        'fundal_height_cm',
        'bmi',
        'vital_signs',
        'lab_results',
        'notes'
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'vital_signs' => 'array',
        'lab_results' => 'array',
        'weight_kg' => 'decimal:2',
        'blood_pressure_systolic' => 'decimal:2',
        'blood_pressure_diastolic' => 'decimal:2',
        'blood_sugar' => 'decimal:2',
        'fundal_height_cm' => 'decimal:2',
        'bmi' => 'decimal:2'
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
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('measurement_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('measurement_date', 'desc');
    }

    public function scopeByMetric($query, $metric)
    {
        return $query->whereNotNull($metric);
    }

    // Helper methods
    public function getBloodPressure()
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
        }
        return null;
    }

    public function getWeightGain()
    {
        if ($this->pregnancyRecord && $this->pregnancyRecord->pre_pregnancy_weight && $this->weight_kg) {
            return $this->weight_kg - $this->pregnancyRecord->pre_pregnancy_weight;
        }
        return null;
    }

    public function isBloodPressureNormal()
    {
        if (!$this->blood_pressure_systolic || !$this->blood_pressure_diastolic) {
            return null;
        }
        
        return $this->blood_pressure_systolic < 140 && $this->blood_pressure_diastolic < 90;
    }

    public function isBloodSugarNormal()
    {
        if (!$this->blood_sugar) {
            return null;
        }
        
        return $this->blood_sugar < 140; // Fasting blood sugar normal range
    }
} 