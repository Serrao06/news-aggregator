<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title', 
        'description', 
        'author',
        'url', 
        'source', 
        'category', 
        'published_at', 
        'provider'
    ];

    // casts
    protected $casts = [
        'published_at' => 'datetime', 
    ];

}
