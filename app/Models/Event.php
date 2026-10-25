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
        'is_top_event',
        'price_label',
        'capacity_label',
        'registration_email',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_crawled' => 'boolean',
        'needs_review' => 'boolean',
        'published_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_top_event' => 'boolean',
    ];

    public function registrationTargetEmail(): ?string
    {
        if ($this->registration_email) {
            return $this->registration_email;
        }

        return $this->expert?->email;
    }

    public function isUpcoming(): bool
    {
        if ($this->starts_on === null) {
            return true;
        }

        return $this->starts_on->gte(now()->startOfDay());
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }
}
