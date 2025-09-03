<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class TeamMember extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'role',
        'description',
        'image_url',
        'status',
        'sort_order',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'created_by'
    ];

    /**
     * Get the user who created this team member
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active team members
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for ordered team members
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get team member data formatted for frontend
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'role' => $this->role,
            'description' => $this->description,
            'imageUrl' => $this->image_url,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
            'metadata' => $this->metadata
        ];
    }
}
