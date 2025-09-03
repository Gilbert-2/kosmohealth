<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PregnancyRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'lmp_date',
        'conception_date',
        'due_date',
        'ultrasound_date',
        'ultrasound_due_date',
        'gestational_age_weeks',
        'gestational_age_days',
        'trimester',
        'status',
        'medical_history',
        'pregnancy_complications',
        'pre_pregnancy_weight',
        'current_weight',
        'notes'
    ];

    protected $casts = [
        'lmp_date' => 'date',
        'conception_date' => 'date',
        'due_date' => 'date',
        'ultrasound_date' => 'date',
        'ultrasound_due_date' => 'date',
        'medical_history' => 'array',
        'pregnancy_complications' => 'array',
        'pre_pregnancy_weight' => 'decimal:2',
        'current_weight' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function symptoms()
    {
        return $this->hasMany(PregnancySymptom::class);
    }

    public function appointments()
    {
        return $this->hasMany(PregnancyAppointment::class);
    }

    public function milestones()
    {
        return $this->hasMany(BabyDevelopmentMilestone::class);
    }

    public function healthMetrics()
    {
        return $this->hasMany(PregnancyHealthMetric::class);
    }

    public function riskFactors()
    {
        return $this->hasMany(PregnancyRiskFactor::class);
    }

    // Calculations
    public function calculateGestationalAge()
    {
        $lmp = Carbon::parse($this->lmp_date);
        $today = Carbon::today();
        
        $days = $lmp->diffInDays($today);
        $weeks = floor($days / 7);
        $remainingDays = $days % 7;
        
        $this->gestational_age_weeks = $weeks;
        $this->gestational_age_days = $remainingDays;
        
        return [
            'weeks' => $weeks,
            'days' => $remainingDays,
            'total_days' => $days
        ];
    }

    public function calculateDueDate()
    {
        if ($this->ultrasound_due_date) {
            return $this->ultrasound_due_date;
        }
        
        // Naegele's rule: LMP + 7 days + 9 months
        $dueDate = Carbon::parse($this->lmp_date)
            ->addDays(7)
            ->addMonths(9);
        
        $this->due_date = $dueDate;
        return $dueDate;
    }

    public function calculateConceptionDate()
    {
        // Conception typically occurs 14 days after LMP
        $conceptionDate = Carbon::parse($this->lmp_date)->addDays(14);
        $this->conception_date = $conceptionDate;
        return $conceptionDate;
    }

    public function calculateTrimester()
    {
        $weeks = $this->gestational_age_weeks;
        
        if ($weeks <= 12) {
            $this->trimester = '1st';
        } elseif ($weeks <= 27) {
            $this->trimester = '2nd';
        } else {
            $this->trimester = '3rd';
        }
        
        return $this->trimester;
    }

    public function getDaysUntilDue()
    {
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    public function getPregnancyProgress()
    {
        $totalDays = 280; // 40 weeks * 7 days
        $elapsedDays = Carbon::parse($this->lmp_date)->diffInDays(Carbon::today());
        $progress = min(100, max(0, ($elapsedDays / $totalDays) * 100));
        
        return round($progress, 1);
    }

    public function getCurrentWeekInfo()
    {
        $this->calculateGestationalAge();
        $this->calculateTrimester();
        
        return [
            'week_number' => $this->gestational_age_weeks,
            'day_number' => $this->gestational_age_days,
            'trimester' => $this->trimester,
            'days_until_due' => $this->getDaysUntilDue(),
            'progress_percentage' => $this->getPregnancyProgress()
        ];
    }

    public function getBabySizeInfo()
    {
        $week = $this->gestational_age_weeks;
        
        $sizeChart = [
            4 => ['size' => 'Poppy seed', 'length' => 0.1, 'weight' => 0.1],
            5 => ['size' => 'Sesame seed', 'length' => 0.2, 'weight' => 0.2],
            6 => ['size' => 'Lentil', 'length' => 0.4, 'weight' => 0.4],
            7 => ['size' => 'Blueberry', 'length' => 0.7, 'weight' => 0.7],
            8 => ['size' => 'Kidney bean', 'length' => 1.2, 'weight' => 1.2],
            9 => ['size' => 'Grape', 'length' => 1.8, 'weight' => 1.8],
            10 => ['size' => 'Kumquat', 'length' => 2.5, 'weight' => 2.5],
            11 => ['size' => 'Fig', 'length' => 3.5, 'weight' => 3.5],
            12 => ['size' => 'Lime', 'length' => 4.5, 'weight' => 4.5],
            13 => ['size' => 'Lemon', 'length' => 5.5, 'weight' => 5.5],
            14 => ['size' => 'Peach', 'length' => 6.5, 'weight' => 6.5],
            15 => ['size' => 'Apple', 'length' => 7.5, 'weight' => 7.5],
            16 => ['size' => 'Avocado', 'length' => 8.5, 'weight' => 8.5],
            17 => ['size' => 'Pear', 'length' => 9.5, 'weight' => 9.5],
            18 => ['size' => 'Bell pepper', 'length' => 10.5, 'weight' => 10.5],
            19 => ['size' => 'Mango', 'length' => 11.5, 'weight' => 11.5],
            20 => ['size' => 'Banana', 'length' => 12.5, 'weight' => 12.5],
            21 => ['size' => 'Carrot', 'length' => 13.5, 'weight' => 13.5],
            22 => ['size' => 'Coconut', 'length' => 14.5, 'weight' => 14.5],
            23 => ['size' => 'Grapefruit', 'length' => 15.5, 'weight' => 15.5],
            24 => ['size' => 'Corn', 'length' => 16.5, 'weight' => 16.5],
            25 => ['size' => 'Cauliflower', 'length' => 17.5, 'weight' => 17.5],
            26 => ['size' => 'Lettuce', 'length' => 18.5, 'weight' => 18.5],
            27 => ['size' => 'Broccoli', 'length' => 19.5, 'weight' => 19.5],
            28 => ['size' => 'Eggplant', 'length' => 20.5, 'weight' => 20.5],
            29 => ['size' => 'Butternut squash', 'length' => 21.5, 'weight' => 21.5],
            30 => ['size' => 'Cabbage', 'length' => 22.5, 'weight' => 22.5],
            31 => ['size' => 'Pineapple', 'length' => 23.5, 'weight' => 23.5],
            32 => ['size' => 'Squash', 'length' => 24.5, 'weight' => 24.5],
            33 => ['size' => 'Pineapple', 'length' => 25.5, 'weight' => 25.5],
            34 => ['size' => 'Cantaloupe', 'length' => 26.5, 'weight' => 26.5],
            35 => ['size' => 'Honeydew melon', 'length' => 27.5, 'weight' => 27.5],
            36 => ['size' => 'Romaine lettuce', 'length' => 28.5, 'weight' => 28.5],
            37 => ['size' => 'Swiss chard', 'length' => 29.5, 'weight' => 29.5],
            38 => ['size' => 'Leek', 'length' => 30.5, 'weight' => 30.5],
            39 => ['size' => 'Mini watermelon', 'length' => 31.5, 'weight' => 31.5],
            40 => ['size' => 'Pumpkin', 'length' => 32.5, 'weight' => 32.5]
        ];
        
        return $sizeChart[$week] ?? $sizeChart[40];
    }

    public function getRiskAssessment()
    {
        $risks = [];
        
        // Age-based risks
        $userAge = $this->user->age ?? 25;
        if ($userAge < 18) {
            $risks[] = ['type' => 'age', 'level' => 'high', 'description' => 'Teen pregnancy risks'];
        } elseif ($userAge > 35) {
            $risks[] = ['type' => 'age', 'level' => 'medium', 'description' => 'Advanced maternal age'];
        }
        
        // Weight-based risks
        if ($this->pre_pregnancy_weight) {
            $bmi = $this->pre_pregnancy_weight / pow(1.6, 2); // Assuming average height
            if ($bmi < 18.5) {
                $risks[] = ['type' => 'weight', 'level' => 'medium', 'description' => 'Underweight'];
            } elseif ($bmi > 30) {
                $risks[] = ['type' => 'weight', 'level' => 'high', 'description' => 'Obesity'];
            }
        }
        
        // Medical history risks
        if ($this->medical_history) {
            foreach ($this->medical_history as $condition) {
                $risks[] = [
                    'type' => 'medical',
                    'level' => 'high',
                    'description' => "Pre-existing condition: {$condition}"
                ];
            }
        }
        
        return $risks;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
} 