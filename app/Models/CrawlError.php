<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlError extends Model
{
    protected $fillable = [
        'crawl_job_id',
        'url',
        'error_type',
        'message',
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }
}
