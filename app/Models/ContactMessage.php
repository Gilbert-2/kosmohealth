<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class ContactMessage extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'message',
        'subject',
        'phone',
        'status',
        'priority',
        'ip_address',
        'user_agent',
        'email_sent_at',
        'email_response',
        'metadata',
        'assigned_to',
        'admin_notes'
    ];

    protected $casts = [
        'email_response' => 'array',
        'metadata' => 'array',
        'email_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = [
        'id',
        'ip_address',
        'user_agent',
        'email_response'
    ];

    /**
     * Get the admin assigned to this message
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for new messages
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for unread messages (new + read)
     */
    public function scopeUnread($query)
    {
        return $query->whereIn('status', ['new', 'read']);
    }

    /**
     * Scope for messages by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for messages by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get message data formatted for admin
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'phone' => $this->phone,
            'status' => $this->status,
            'priority' => $this->priority,
            'emailSentAt' => $this->email_sent_at?->toISOString(),
            'assignedTo' => $this->assignedAdmin?->name,
            'adminNotes' => $this->admin_notes,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString()
        ];
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): bool
    {
        if ($this->status === 'new') {
            return $this->update(['status' => 'read']);
        }
        return true;
    }

    /**
     * Mark message as replied
     */
    public function markAsReplied(): bool
    {
        return $this->update(['status' => 'replied']);
    }

    /**
     * Archive message
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }
}
