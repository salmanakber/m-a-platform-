<?php

namespace App\Services\Crawl;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\CrawlError;
use App\Models\CrawlJob;
use App\Models\CrawlResult;
use App\Models\Expert;
use App\Services\AI\AIManager;
use App\Support\ArticleStatus;
use App\Support\ExpertStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrawlService
{
    public function __construct(
        private WebsiteCrawler $crawler,
        private AIManager $ai
    ) {
    }

    /**
     * @return Collection<int, Expert>
     */
    public function expertsDueForCrawl(): Collection
    {
        return Expert::query()
            ->where('status', ExpertStatus::APPROVED)
            ->where('crawl_enabled', true)
            ->whereNotNull('website_crawl_url')
            ->get();
    }

    public function startCrawlForExpert(Expert $expert): CrawlJob
    {
        return CrawlJob::query()->create([
            'expert_id' => $expert->id,
            'status' => 'pending',
        ]);
    }

    public function markRunning(CrawlJob $job): void
    {
        $job->update([
            'status' => 'running',
            'started_at' => now(),
            'progress_step' => 'start',
            'progress_label' => 'Import wird vorbereitet…',
            'progress_percent' => 5,
        ]);
    }

    public function markFinished(CrawlJob $job, string $status = 'completed', ?string $notes = null): void
    {
        $job->update([
            'status' => $status,
            'finished_at' => now(),
            'notes' => $notes,
            'progress_step' => $status === 'completed' ? 'done' : 'failed',
            'progress_label' => $status === 'completed' ? 'Import abgeschlossen' : 'Import fehlgeschlagen',
            'progress_percent' => 100,
        ]);
    }

    private function setProgress(CrawlJob $job, string $step, string $label, int $percent): void
    {
        $job->update([
            'progress_step' => $step,
            'progress_label' => $label,
            'progress_percent' => max(0, min(100, $percent)),
        ]);
    }

    /**
     * Full crawl pipeline for one expert (scope §13).
     */
    public function runForExpert(Expert $expert, ?CrawlJob $job = null): CrawlJob
    {
        $job = $job ?: $this->startCrawlForExpert($expert);
        $this->markRunning($job);

        $startUrl = $expert->website_crawl_url ?: $expert->website;

        if (blank($startUrl)) {
            $this->logError($job, null, 'missing_url', 'Keine Crawl-URL hinterlegt.');
            $this->markFinished($job, 'failed', 'Keine Crawl-URL');

            return $job->fresh();
        }

        try {
            $this->setProgress($job, 'discover', 'Website wird gelesen…', 12);
            $this->setProgress($job, 'sitemap', 'Sitemap & Feeds werden geprüft…', 22);

            $discovered = $this->crawler->discoverUrls($startUrl);
            $toProcess = $this->crawler->urlsToProcess($discovered);

            // Guarantee at least the start URL is attempted.
            if ($toProcess === []) {
                $toProcess = [$startUrl];
            }

            $total = max(count($toProcess), 1);
            $job->update([
                'urls_discovered' => max(count($discovered), count($toProcess)),
                'urls_processed' => 0,
            ]);

            $this->setProgress(
                $job,
                'found',
                count($discovered).' Seite(n) gefunden — Inhalte werden geladen…',
                35
            );

            $processed = 0;
            $imported = 0;
            $skipped = 0;
            $failed = 0;

            $defaultCategoryId = ArticleCategory::query()->orderBy('sort_order')->value('id');

            if ($defaultCategoryId === null) {
                $this->logError($job, null, 'missing_category', 'Keine Artikelkategorie vorhanden.');
                $this->markFinished($job, 'failed', 'Keine Kategorie');

                return $job->fresh();
            }

            foreach ($toProcess as $url) {
                $processed++;
                $hostPath = parse_url($url, PHP_URL_PATH) ?: $url;
                $short = \Illuminate\Support\Str::limit(ltrim((string) $hostPath, '/') ?: $url, 48);
                $pct = 35 + (int) floor(($processed / $total) * 55);

                $this->setProgress($job, 'fetch', 'Lade: '.$short, $pct);

                try {
                    $outcome = $this->importUrl($job, $expert, $url, (int) $defaultCategoryId);
                    if ($outcome === 'imported') {
                        $imported++;
                        $this->setProgress($job, 'import', 'Übernommen: '.$short, min(92, $pct + 2));
                    } elseif ($outcome === 'failed') {
                        $failed++;
                    } else {
                        $skipped++;
                        $this->setProgress($job, 'skip', 'Übersprungen (bereits vorhanden): '.$short, $pct);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->logError($job, $url, 'extract_failed', $e->getMessage());
                    CrawlResult::query()->create([
                        'crawl_job_id' => $job->id,
                        'url' => $url,
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                    ]);
                }

                $job->update(['urls_processed' => $processed]);
            }

            $this->setProgress($job, 'finalize', 'Ergebnisse werden gespeichert…', 96);

            $summary = "Entdeckt: {$job->urls_discovered}, verarbeitet: {$processed}, importiert: {$imported}, übersprungen: {$skipped}";
            if ($failed > 0) {
                $summary .= ", fehlgeschlagen: {$failed}";
            }
            if ($imported === 0 && $processed > 0) {
                $summary .= '. Tipp: Eine Blog-/News-URL verwenden (z. B. …/blog) oder prüfen, ob die Seite erreichbar ist.';
            }

            $this->markFinished($job, 'completed', $summary);
        } catch (\Throwable $e) {
            $this->logError($job, $startUrl, 'job_failed', $e->getMessage());
            $this->markFinished($job, 'failed', $e->getMessage());
        }

        return $job->fresh();
    }

    private function importUrl(CrawlJob $job, Expert $expert, string $url, int $defaultCategoryId): string
    {
        if (Article::query()->where('source_url', $url)->exists()) {
            CrawlResult::query()->create([
                'crawl_job_id' => $job->id,
                'url' => $url,
                'status' => 'duplicate',
                'message' => 'URL bereits vorhanden',
            ]);

            return 'skipped';
        }

        $extracted = $this->crawler->extractArticle($url);

        if ($extracted === null) {
            CrawlResult::query()->create([
                'crawl_job_id' => $job->id,
                'url' => $url,
                'status' => 'skipped',
                'message' => 'Kein ausreichender Artikelinhalt',
            ]);

            return 'skipped';
        }

        $fingerprint = $this->crawler->fingerprint($url, $extracted['title'], $extracted['body']);

        if (Article::query()->where('content_fingerprint', $fingerprint)->exists()) {
            CrawlResult::query()->create([
                'crawl_job_id' => $job->id,
                'url' => $url,
                'status' => 'duplicate',
                'content_fingerprint' => $fingerprint,
                'message' => 'Inhalt bereits vorhanden',
            ]);

            return 'skipped';
        }

        $normalized = $this->normalizeWithAi($extracted, $expert);
        $categoryId = $this->resolveCategoryId($normalized['category'] ?? null, $defaultCategoryId);

        $coverPath = null;
        if (! empty($extracted['image_url'])) {
            $coverPath = $this->storeRemoteImage($extracted['image_url']);
        }

        $article = Article::query()->create([
            'expert_id' => $expert->id,
            'category_id' => $categoryId,
            'title' => $normalized['title'] ?? $extracted['title'],
            'slug' => $this->uniqueSlug($normalized['title'] ?? $extracted['title']),
            'excerpt' => $normalized['excerpt'] ?? $extracted['excerpt'],
            'body' => $normalized['body'] ?? $extracted['body'],
            'cover_image' => $coverPath,
            'status' => ArticleStatus::PUBLISHED,
            'source_url' => $url,
            'source_attribution' => $expert->company_name.' · '.$url,
            'content_fingerprint' => $fingerprint,
            'is_crawled' => true,
            'published_at' => now(),
            'seo_title' => $normalized['title'] ?? $extracted['title'],
            'seo_description' => Str::limit(strip_tags($normalized['excerpt'] ?? $extracted['excerpt']), 160),
        ]);

        CrawlResult::query()->create([
            'crawl_job_id' => $job->id,
            'url' => $url,
            'article_id' => $article->id,
            'status' => 'imported',
            'content_fingerprint' => $fingerprint,
            'message' => 'Artikel importiert und veröffentlicht',
        ]);

        return 'imported';
    }

    /**
     * @param  array{title: string, excerpt: string, body: string, image_url: ?string}  $extracted
     * @return array{title?: string, excerpt?: string, body?: string, category?: string}
     */
    private function normalizeWithAi(array $extracted, Expert $expert): array
    {
        try {
            $prompt = <<<PROMPT
Du bist Redakteur für nachfolge-experten.ch (Schweizer M&A / Unternehmensnachfolge).
Bereinige und strukturiere den folgenden gecrawlten Artikel.
Antworte NUR mit gültigem JSON-Objekt (kein Markdown):
{"title":"...","excerpt":"...","body":"...","category":"..."}

Regeln:
- Sprache: Deutsch (Schweiz)
- body als einfache HTML-Absätze (<p>...</p>)
- excerpt max. 220 Zeichen
- category eine von: Grundlagen und strategische Optionen, Unternehmensbewertung, Vorbereitung, Psychologie und Kommunikation, Recht, Steuern & Risiken, Praxisberichte
- Firma: {$expert->company_name}

Titel: {$extracted['title']}

Inhalt:
{$extracted['body']}
PROMPT;

            $raw = $this->ai->complete($prompt, 'crawl_article_normalize');
            $json = $this->extractJsonObject($raw);

            if ($json === null) {
                return [];
            }

            return array_filter([
                'title' => isset($json['title']) ? Str::limit((string) $json['title'], 240) : null,
                'excerpt' => isset($json['excerpt']) ? Str::limit((string) $json['excerpt'], 500) : null,
                'body' => isset($json['body']) ? (string) $json['body'] : null,
                'category' => isset($json['category']) ? (string) $json['category'] : null,
            ], static fn ($value) => $value !== null && $value !== '');
        } catch (\Throwable) {
            // Crawl must succeed without AI.
            return [];
        }
    }

    private function resolveCategoryId(?string $name, int $fallbackId): int
    {
        if ($name === null || trim($name) === '') {
            return $fallbackId;
        }

        $found = ArticleCategory::query()
            ->where(function ($query) use ($name) {
                $query->where('name_de', $name)
                    ->orWhere('slug', Str::slug($name));
            })
            ->value('id');

        return $found ? (int) $found : $fallbackId;
    }

    private function storeRemoteImage(string $url): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $mime = strtolower((string) $response->header('Content-Type'));
            $ext = 'jpg';
            if (str_contains($mime, 'png')) {
                $ext = 'png';
            } elseif (str_contains($mime, 'webp')) {
                $ext = 'webp';
            } elseif (str_contains($mime, 'gif')) {
                $ext = 'gif';
            }

            $path = 'articles/covers/'.Str::random(40).'.'.$ext;
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : Str::random(8);
        $original = $slug;
        $i = 1;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function logError(CrawlJob $job, ?string $url, string $type, string $message): void
    {
        CrawlError::query()->create([
            'crawl_job_id' => $job->id,
            'url' => $url,
            'error_type' => $type,
            'message' => Str::limit($message, 2000),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function extractJsonObject(string $raw): ?array
    {
        $raw = trim($raw);
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
