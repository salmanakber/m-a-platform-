<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiSuggestion extends Model
{
    protected $fillable = [
        'expert_id',
        'suggestible_type',
        'suggestible_id',
        'field_name',
        'current_value',
        'suggested_value',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'ai_run_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function suggestible(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function aiRun(): BelongsTo
    {
        return $this->belongsTo(AiRun::class);
    }
}
