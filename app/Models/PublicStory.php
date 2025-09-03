<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUuid;

class PublicStory extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'original_story_uuid',
        'title',
        'content',
        'category',
        'tags',
        'anonymous_author',
        'author_metadata',
        'is_featured',
        'sort_order',
        'views_count',
        'likes_count',
        'published_at',
        'published_by',
        'metadata'
    ];

    protected $casts = [
        'tags' => 'array',
        'author_metadata' => 'array',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'original_story_uuid', // Never expose in public APIs
        'published_by' // Never expose admin info in public APIs
    ];

    /**
     * Get the admin who published this story
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get all interactions for this story
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(StoryInteraction::class);
    }

    /**
     * Get likes for this story
     */
    public function likes(): HasMany
    {
        return $this->hasMany(StoryInteraction::class)->where('interaction_type', 'like');
    }

    /**
     * Get views for this story
     */
    public function views(): HasMany
    {
        return $this->hasMany(StoryInteraction::class)->where('interaction_type', 'view');
    }

    /**
     * Scope for stories by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for featured stories
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for recent stories
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for popular stories (by likes)
     */
    public function scopePopular($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    /**
     * Scope for most viewed stories
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    /**
     * Increment view count
     */
    public function incrementViews(): bool
    {
        return $this->increment('views_count');
    }

    /**
     * Increment likes count
     */
    public function incrementLikes(): bool
    {
        return $this->increment('likes_count');
    }

    /**
     * Decrement likes count
     */
    public function decrementLikes(): bool
    {
        return $this->decrement('likes_count');
    }

    /**
     * Check if user has liked this story
     */
    public function isLikedByUser(int $userId): bool
    {
        return $this->interactions()
            ->where('user_id', $userId)
            ->where('interaction_type', 'like')
            ->exists();
    }

    /**
     * Get the admin who published this story
     */
    public function publishedByAdmin()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get story data for public display (completely anonymous)
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'tags' => $this->tags,
            'author' => $this->anonymous_author,
            'authorMetadata' => $this->author_metadata,
            'isFeatured' => $this->is_featured,
            'viewsCount' => $this->views_count,
            'likesCount' => $this->likes_count,
            'publishedAt' => $this->published_at->toISOString(),
            'publishedDate' => $this->published_at->format('Y-m-d'),
            'publishedTime' => $this->published_at->format('H:i:s'),
            'publishedDateTime' => $this->published_at->format('Y-m-d H:i:s'),
            'publishedHuman' => $this->published_at->diffForHumans(),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
            'metadata' => $this->metadata
        ];
    }

    /**
     * Get story data for public display with user interaction context
     */
    public function toPublicArrayWithUserContext(int $userId): array
    {
        $data = $this->toPublicArray();
        $data['isLiked'] = $this->isLikedByUser($userId);
        return $data;
    }

    /**
     * Get story data for admin dashboard (includes admin context)
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->uuid,
            'originalStoryId' => $this->original_story_uuid,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'tags' => $this->tags,
            'author' => $this->anonymous_author,
            'authorMetadata' => $this->author_metadata,
            'isFeatured' => $this->is_featured,
            'sortOrder' => $this->sort_order,
            'viewsCount' => $this->views_count,
            'likesCount' => $this->likes_count,
            'publishedAt' => $this->published_at->toISOString(),
            'publisherName' => $this->publisher?->name,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString()
        ];
    }
}
