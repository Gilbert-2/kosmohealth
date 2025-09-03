<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class StoryInteraction extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'public_story_id',
        'interaction_type',
        'ip_address',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'user_id', // Never expose user_id in public APIs
        'ip_address' // Never expose IP in public APIs
    ];

    /**
     * Get the user who made this interaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the story this interaction is for
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(PublicStory::class, 'public_story_id');
    }

    /**
     * Scope for interactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for interactions by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for interactions by story
     */
    public function scopeByStory($query, int $storyId)
    {
        return $query->where('public_story_id', $storyId);
    }

    /**
     * Scope for likes
     */
    public function scopeLikes($query)
    {
        return $query->where('interaction_type', 'like');
    }

    /**
     * Scope for views
     */
    public function scopeViews($query)
    {
        return $query->where('interaction_type', 'view');
    }

    /**
     * Scope for reports
     */
    public function scopeReports($query)
    {
        return $query->where('interaction_type', 'report');
    }

    /**
     * Create or update interaction
     */
    public static function createOrUpdate(int $userId, int $storyId, string $type, array $data = []): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'public_story_id' => $storyId,
                'interaction_type' => $type
            ],
            [
                'ip_address' => $data['ip_address'] ?? null,
                'metadata' => $data['metadata'] ?? null
            ]
        );
    }

    /**
     * Get interaction data for analytics (anonymized)
     */
    public function toAnalyticsArray(): array
    {
        return [
            'id' => $this->uuid,
            'storyId' => $this->story->uuid,
            'interactionType' => $this->interaction_type,
            'createdAt' => $this->created_at->toISOString(),
            'metadata' => $this->metadata
        ];
    }
}
