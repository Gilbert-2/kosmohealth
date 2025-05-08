<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'document_path',
        'selfie_path',
        'verification_data',
        'liveness_check',
        'face_match_score',
        'verification_id',
        'completed_at'
    ];

    protected $casts = [
        'verification_data' => 'array',
        'liveness_check' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete()
    {
        return $this->status === 'verified' && $this->completed_at !== null;
    }

    public function markAsComplete()
    {
        $this->status = 'verified';
        $this->completed_at = now();
        $this->verification_id = 'KYC-' . uniqid();
        $this->save();
    }
}