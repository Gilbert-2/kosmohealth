<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'title', 'excerpt', 'body', 'category', 'tags',
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
}


