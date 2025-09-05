<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'title', 'excerpt', 'body', 'image_path', 'category', 'tags',
        'read_time', 'rating', 'views', 'status'
    ];

    protected $casts = [
        'tags' => 'array',
        'rating' => 'float',
        'views' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Accessor for full image URL
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        // If already a full URL, return as-is
        if (preg_match('/^https?:\/\//i', $this->image_path)) {
            return $this->image_path;
        }
        $base = rtrim(config('app.url', url('/')), '/');
        return $base . '/storage/' . ltrim($this->image_path, '/');
    }
}


