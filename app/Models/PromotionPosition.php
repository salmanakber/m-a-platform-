<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionPosition extends Model
{
    protected $fillable = [
        'canton_id',
        'position_number',
    ];

    protected $casts = [
        'position_number' => 'integer',
    ];

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }
}
