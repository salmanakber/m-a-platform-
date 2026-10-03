<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRun extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'filename',
        'storage_path',
        'status',
        'rows_total',
        'rows_created',
        'rows_updated',
        'rows_skipped',
        'rows_failed',
        'errors',
        'meta',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
