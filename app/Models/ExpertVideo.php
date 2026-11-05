<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertVideo extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PENDING = 'pending_payment';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'expert_id',
        'title',
        'url',
        'sort_order',
        'is_complimentary',
        'status',
        'invoice_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_complimentary' => 'boolean',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function embedUrl(): ?string
    {
        $url = trim((string) $this->url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
