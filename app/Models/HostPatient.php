<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HostPatient extends Model
{
    use HasFactory;

    protected $fillable = [
        'host_id',
        'patient_id',
        'status',
        'assigned_at',
        'notes',
        'consultation_reason'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'notes' => 'array'
    ];

    /**
     * Get the host (health provider)
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * Get the patient
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
