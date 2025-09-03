<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class Testimonial extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'text',
        'rating',
        'status',
        'sort_order',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'rating' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'created_by'
    ];

    /**
     * Get the user who created this testimonial
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active testimonials
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for ordered testimonials
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Scope for testimonials by rating
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get testimonial data formatted for frontend
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'text' => $this->text,
            'rating' => $this->rating,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
            'metadata' => $this->metadata
        ];
    }
}
