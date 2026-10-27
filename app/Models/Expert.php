<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'logo_path',
        'website',
        'email',
        'phone',
        'description',
        'services_text',
        'offers_buy',
        'offers_sell',
        'professional_group',
        'status',
        'rejection_reason',
        'is_public',
        'contact_person_name',
        'contact_person_last_name',
        'crawl_enabled',
        'crawl_import_articles',
        'crawl_import_events',
        'crawl_use_ai',
        'website_crawl_url',
        'imported_from',
        'import_fingerprint',
        'logo_position',
        'logo_locked',
        'public_rating',
        'public_deals_count',
    ];

    protected $casts = [
        'offers_buy' => 'boolean',
        'offers_sell' => 'boolean',
        'is_public' => 'boolean',
        'crawl_enabled' => 'boolean',
        'crawl_import_articles' => 'boolean',
        'crawl_import_events' => 'boolean',
        'crawl_use_ai' => 'boolean',
        'logo_locked' => 'boolean',
        'public_rating' => 'float',
        'public_deals_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(ExpertOffice::class);
    }

    public function primaryOffice(): ?ExpertOffice
    {
        return $this->offices()->where('is_primary', true)->first()
            ?? $this->offices()->orderBy('sort_order')->first();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function crawlJobs(): HasMany
    {
        return $this->hasMany(CrawlJob::class);
    }

    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiSuggestion::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function promotionWaitlistEntries(): HasMany
    {
        return $this->hasMany(PromotionWaitlist::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ExpertMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Platform operator (KMU Beratungen) — label publicly to avoid "independent" conflict.
     */
    public function isPlatformOperator(): bool
    {
        $name = mb_strtolower(trim((string) $this->company_name));
        $email = mb_strtolower(trim((string) $this->email));
        $website = mb_strtolower(trim((string) $this->website));

        return str_contains($name, 'kmu beratungen')
            || str_contains($email, 'kmuberatungen')
            || str_contains($website, 'kmuberatungen');
    }
}
