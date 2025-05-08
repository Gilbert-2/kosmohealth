<?php

namespace App\Models;

use App\Traits\HasMeta;
use App\Traits\HasUuid;
use App\Traits\ModelOption;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Poll extends Model
{
    use HasMeta, HasUuid, ModelOption, HasFactory, LogsActivity;

    protected $guarded = [];
    protected $casts = [
        'meta' => 'array',
        'options' => 'array',
        'start_at' => 'datetime'
    ];
    protected $table = 'polls';
    protected static $sortOptions = ['created_at'];
    protected static $defaultSortBy = 'created_at';

    // Relations
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meeting() : BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function results() : HasMany
    {
        return $this->hasMany(PollResult::class, 'poll_id');
    }

    public function getEndAtAttribute()
    {
        return Carbon::parse($this->start_at)->addSeconds($this->duration);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('poll')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
