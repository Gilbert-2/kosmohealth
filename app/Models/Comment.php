<?php

namespace App\Models;

use App\Traits\HasMeta;
use App\Traits\HasUuid;
use App\Models\User;
use App\Traits\ModelOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Comment extends Model implements HasMedia
{
    use HasMeta, HasUuid, ModelOption, InteractsWithMedia, LogsActivity;

    protected $guarded = [];
    protected $casts = [
        'meta' => 'array'
    ];
    protected $table = 'comments';
    protected static $sortOptions = ['created_at', 'updated_at'];
    protected static $defaultSortBy = 'created_at';

    // Relations
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    // Booted
    public static function booted()
    {
    }

    protected static function ensureUpdatable() : void
    {
    }

    // Constraints

    public function isValid($meeting) : void
    {
        $commentable = $this->commentable;

        if (! $commentable instanceof Meeting) {
            throw ValidationException::withMessages(['message' => __('general.invalid_action')]);
        }

        if ($commentable->id != $meeting->id) {
            throw ValidationException::withMessages(['message' => __('general.invalid_action')]);
        }
    }

    // Filters

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('comment')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
