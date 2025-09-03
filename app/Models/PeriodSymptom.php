<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PeriodSymptom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cycle_id',
        'date',
        'symptom',
        'flow_intensity',
        'mood',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cycle()
    {
        return $this->belongsTo(PeriodCycle::class, 'cycle_id');
    }
}