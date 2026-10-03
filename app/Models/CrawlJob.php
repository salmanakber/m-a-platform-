<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlJob extends Model
{
    protected $fillable = [
        'expert_id',
        'status',
        'started_at',
        'finished_at',
        'urls_discovered',
        'urls_processed',
        'notes',
        'progress_step',
        'progress_label',
        'progress_percent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'urls_discovered' => 'integer',
        'urls_processed' => 'integer',
        'progress_percent' => 'integer',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(CrawlResult::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(CrawlError::class);
    }
}
