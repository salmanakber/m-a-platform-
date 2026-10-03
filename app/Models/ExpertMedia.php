<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertMedia extends Model
{
    protected $table = 'expert_media';

    protected $fillable = [
        'expert_id',
        'path',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }
}
