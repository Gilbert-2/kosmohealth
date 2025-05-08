<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoketiApp extends Model
{
    protected $guarded = [];
    protected $casts = [
        'webhooks' => 'array',
    ];
    protected $table = 'soketi_apps';
    public $timestamps = false;
}
