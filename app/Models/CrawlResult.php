<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlResult extends Model
{
    protected $fillable = [
        'crawl_job_id',
        'url',
        'article_id',
        'status',
        'content_fingerprint',
        'message',
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
