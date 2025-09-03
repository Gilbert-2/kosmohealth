<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class ContactInfo extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'contact_info';

    protected $fillable = [
        'uuid',
        'email',
        'phone',
        'address',
        'business_hours',
        'support_email',
        'social_links',
        'status',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'social_links' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'created_by'
    ];

    /**
     * Get the user who created this contact info
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active contact info
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get contact info data formatted for frontend
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'businessHours' => $this->business_hours,
            'socialLinks' => $this->social_links,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Get contact info data formatted for admin
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'businessHours' => $this->business_hours,
            'supportEmail' => $this->support_email,
            'socialLinks' => $this->social_links,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString()
        ];
    }

    /**
     * Get the current active contact info
     */
    public static function getCurrent(): ?self
    {
        return self::active()->latest()->first();
    }
}
