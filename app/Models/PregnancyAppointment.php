<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PregnancyAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pregnancy_record_id',
        'user_id',
        'appointment_type',
        'doctor_name',
        'clinic_name',
        'clinic_address',
        'clinic_phone',
        'appointment_date',
        'status',
        'notes',
        'results',
        'appointment_data',
        'reminder_sent'
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'appointment_data' => 'array',
        'reminder_sent' => 'boolean'
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
        return $query->where('appointment_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>', now())
                    ->where('status', 'scheduled');
    }

    public function scopePast($query)
    {
        return $query->where('appointment_date', '<', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
} 