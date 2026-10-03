<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionWaitlist extends Model
{
    protected $table = 'promotion_waitlist';

    protected $fillable = [
        'expert_id',
        'canton_id',
        'position_number',
        'queue_order',
    ];

    protected $casts = [
        'position_number' => 'integer',
        'queue_order' => 'integer',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }
}
