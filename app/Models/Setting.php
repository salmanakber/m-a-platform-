<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
        'is_sensitive',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_sensitive' => 'boolean',
    ];
}
