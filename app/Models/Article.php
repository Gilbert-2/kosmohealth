<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class Article extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'image_url',
        'status',
        'sort_order',
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
     * Get the user who created this article
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for published articles
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for ordered articles
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Add a global default ordering so lists are consistently ordered everywhere
     */
    protected static function booted()
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
        });
    }

    /**
     * Scope for featured articles (top sort_order or metadata flag)
     */
    public function scopeFeatured($query)
    {
        return $query->where(function($q) {
            $q->where('sort_order', 0) // Top priority
              ->orWhereJsonContains('metadata->is_featured', true);
        });
    }

    /**
     * Get formatted date for frontend
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('Y-m-d');
    }

    /**
     * Get article data formatted for frontend
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'desc' => $this->description,
            'date' => $this->formatted_date,
            'imageUrl' => $this->image_url,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Get article data formatted for public API (UI expects these exact keys)
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
        ];
    }

    /**
     * Ensure image_url is always a full URL if a storage path was saved
     */
    public function getImageUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // If already absolute URL, return as is
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        // Normalize common stored paths
        $path = ltrim((string) $value, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        if (str_starts_with($path, 'editor-images/') || str_starts_with($path, 'public/')) {
            return asset('storage/' . $path);
        }

        // Fallback to asset with storage prefix
        return asset('storage/' . $path);
    }

    /**
     * Ensure both snake_case and camelCase image keys are available
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['imageUrl'] = $this->image_url;
        return $array;
    }
}
