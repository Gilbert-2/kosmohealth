<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class AboutUs extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'about_us';

    protected $fillable = [
        'uuid',
        'mission',
        'vision',
        'values',
        'story',
        'status',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'created_by'
    ];

    /**
     * Get the user who created this about us entry
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active about us entries
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get about us data formatted for frontend
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->uuid,
            'mission' => $this->mission,
            'vision' => $this->vision,
            'values' => $this->values,
            'story' => $this->story,
            'status' => $this->status,
            'metadata' => $this->metadata
        ];
    }
}
