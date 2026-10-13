<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'expert_id',
        'title',
        'slug',
        'description',
        'event_type',
        'starts_on',
        'starts_at_time',
        'ends_on',
        'ends_at_time',
        'location_name',
        'address_line',
        'postal_code',
        'city',
        'canton_id',
        'latitude',
        'longitude',
        'source_url',
        'source_attribution',
        'content_fingerprint',
        'is_crawled',
        'needs_review',
        'status',
        'published_at',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_crawled' => 'boolean',
        'needs_review' => 'boolean',
        'published_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
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
