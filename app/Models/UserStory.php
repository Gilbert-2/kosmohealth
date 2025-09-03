<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class UserStory extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'content',
        'category',
        'tags',
        'status',
        'admin_notes',
        'submitted_at',
        'approved_at',
        'approved_by',
        'metadata',
        'is_featured',
        'sort_order'
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'user_id' // Never expose user_id in public APIs
    ];

    /**
     * Get the user who created this story
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who approved this story
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for stories by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for stories by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for user's own stories
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for submitted stories (pending admin review)
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope for approved stories
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for featured stories
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Submit story for admin review
     */
    public function submit(): bool
    {
        return $this->update([
            'status' => 'submitted',
            'submitted_at' => now()
        ]);
    }

    /**
     * Approve story for public display
     */
    public function approve(int $adminId, array $publicData = []): bool
    {
        $updated = $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId
        ]);

        if ($updated) {
            // Create public story record
            $this->createPublicStory($adminId, $publicData);
        }

        return $updated;
    }

    /**
     * Reject story
     */
    public function reject(string $reason = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason
        ]);
    }

    /**
     * Create public story record (anonymous)
     */
    protected function createPublicStory(int $adminId, array $publicData = [])
    {
        return PublicStory::create([
            'original_story_uuid' => $this->uuid,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'tags' => $this->tags,
            'anonymous_author' => $publicData['anonymous_author'] ?? 'Anonymous',
            'author_metadata' => $publicData['author_metadata'] ?? null,
            'is_featured' => $this->is_featured ?? false,
            'sort_order' => $this->sort_order ?? 0,
            'published_at' => now(),
            'published_by' => $adminId,
            'metadata' => $publicData['metadata'] ?? null
        ]);
    }

    /**
     * Get story data for user dashboard (includes user context)
     */
    public function toUserArray(): array
    {
        $data = [
            'id' => $this->uuid,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'tags' => $this->tags,
            'status' => $this->status,
            'submittedAt' => $this->submitted_at?->toISOString(),
            'approvedAt' => $this->approved_at?->toISOString(),
            'isFeatured' => $this->is_featured,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString()
        ];

        // Add information about public version if story was approved
        if ($this->status === 'approved') {
            $publicStory = PublicStory::where('original_story_uuid', $this->uuid)->first();
            if ($publicStory) {
                $data['hasPublicVersion'] = true;
                $data['publicStoryId'] = $publicStory->uuid;
                $data['publicViewsCount'] = $publicStory->views_count;
                $data['publicLikesCount'] = $publicStory->likes_count;
                $data['publicPublishedAt'] = $publicStory->published_at->toISOString();
                $data['deletionNote'] = 'Deleting this story will remove it from your dashboard but keep the anonymous public version available to the community.';
            } else {
                $data['hasPublicVersion'] = false;
                $data['deletionNote'] = 'This story can be deleted completely as it has no public version.';
            }
        } else {
            $data['hasPublicVersion'] = false;
            $data['deletionNote'] = 'This story can be deleted completely.';
        }

        return $data;
    }

    /**
     * Get story data for admin dashboard (includes admin context)
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'content' => $this->content,
            'category' => $this->category,
            'tags' => $this->tags,
            'status' => $this->status,
            'authorName' => $this->author?->name,
            'authorEmail' => $this->author?->email,
            'submittedAt' => $this->submitted_at?->toISOString(),
            'approvedAt' => $this->approved_at?->toISOString(),
            'approverName' => $this->approver?->name,
            'adminNotes' => $this->admin_notes,
            'isFeatured' => $this->is_featured,
            'sortOrder' => $this->sort_order,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString()
        ];
    }
}
