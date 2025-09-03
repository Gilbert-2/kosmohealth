<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingEmotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'host_id',
        'patient_id',
        'timeline',
        'summary',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'timeline' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
