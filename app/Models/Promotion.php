<?php

namespace App\Models;

use App\Support\RankingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'expert_id',
        'scope',
        'professional_group',
        'canton_id',
        'article_category_id',
        'position_number',
        'status',
        'starts_at',
        'ends_at',
        'invoice_id',
    ];

    protected $casts = [
        'position_number' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $attributes = [
        'scope' => RankingScope::CANTON_TOP3,
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }

    public function articleCategory(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeLabel(): string
    {
        return RankingScope::label($this->scope ?: RankingScope::CANTON_TOP3);
    }

    public function occupancyKey(): string
    {
        $scope = $this->scope ?: RankingScope::CANTON_TOP3;

        return implode(':', [
            $scope,
            (string) ($this->professional_group ?: ''),
            (string) ($this->canton_id ?: ''),
            (string) ($this->article_category_id ?: ''),
            (string) $this->position_number,
        ]);
    }
}
