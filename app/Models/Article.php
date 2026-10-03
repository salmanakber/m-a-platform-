<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'expert_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'status',
        'source_url',
        'source_attribution',
        'content_fingerprint',
        'is_crawled',
        'published_at',
        'seo_title',
        'seo_description',
        'blocked_at',
        'blocked_by',
    ];

    protected $casts = [
        'is_crawled' => 'boolean',
        'published_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ArticleImage::class);
    }

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function crawlResults(): HasMany
    {
        return $this->hasMany(CrawlResult::class);
    }
}
