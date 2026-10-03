<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiRun extends Model
{
    protected $fillable = [
        'provider',
        'purpose',
        'status',
        'prompt_excerpt',
        'response',
        'latency_ms',
        'error_message',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
    ];

    public function suggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }
}
