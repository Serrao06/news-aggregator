<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $table = 'user_preferences';

    protected $fillable = [
        'user_id',
        'preference_key',
        'preference_value'
    ];

    protected $casts = [
        'preference_value' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(related: User::class);
    }
}
