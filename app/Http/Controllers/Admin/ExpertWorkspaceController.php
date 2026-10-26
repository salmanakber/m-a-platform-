<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Canton;
use App\Models\CrawlJob;
use App\Models\Event;
use App\Models\Expert;
use App\Services\Article\ArticleAiService;
use App\Services\Crawl\CrawlService;
use App\Services\Event\EventAiService;
use App\Support\ArticleStatus;
use App\Support\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpertWorkspaceController extends Controller
{
    public function __construct(
        private CrawlService $crawlService,
        private ArticleAiService $articleAi,
        private EventAiService $eventAi
    ) {
    }

    public function updateCrawl(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'website_crawl_url' => ['nullable', 'url', 'max:500'],
            'crawl_enabled' => ['nullable', 'boolean'],
            'crawl_import_articles' => ['nullable', 'boolean'],
            'crawl_import_events' => ['nullable', 'boolean'],
            'crawl_use_ai' => ['nullable', 'boolean'],
        ]);

        $url = $validated['website_crawl_url'] ?? null;
        $enabled = $request->boolean('crawl_enabled');

        if ($enabled && blank($url) && blank($expert->website)) {
            return back()->with('error', 'Bitte eine Website-Adresse für den Crawl hinterlegen.');
        }

        $expert->update([
            'website_crawl_url' => $url ?: $expert->website_crawl_url,
            'crawl_enabled' => $enabled,
            'crawl_import_articles' => $request->boolean('crawl_import_articles'),
            'crawl_import_events' => $request->boolean('crawl_import_events'),
            'crawl_use_ai' => $request->boolean('crawl_use_ai'),
        ]);

        return back()->with('success', 'Crawl-Einstellungen gespeichert.')->withFragment('crawl');
    }

    public function runCrawl(Request $request, Expert $expert): JsonResponse|RedirectResponse
    {
        $wantsJson = $request->expectsJson() || $request->ajax();
        $url = $request->input('website_crawl_url')
            ?: $expert->website_crawl_url
            ?: $expert->website;

        if (blank($url)) {
            $message = 'Keine Website-URL hinterlegt.';

            return $wantsJson
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message)->withFragment('crawl');
        }

        $active = CrawlJob::query()
            ->where('expert_id', $expert->id)
            ->whereIn('status', ['pending', 'running'])
            ->orderByDesc('id')
            ->first();

        if ($active) {
            return $wantsJson
                ? response()->json([
                    'message' => 'Ein Crawl läuft bereits.',
                    'job_id' => $active->id,
                    'status_url' => route('admin.experts.crawl.status', [$expert, $active]),
                    'process_url' => route('admin.experts.crawl.process', [$expert, $active]),
                ], 409)
                : back()->with('error', 'Ein Crawl läuft bereits.')->withFragment('crawl');
        }

        $expert->update([
            'website_crawl_url' => $url,
            'crawl_enabled' => true,
        ]);

        $expert = $expert->fresh();
        $job = $this->crawlService->startCrawlForExpert($expert);
        $job->update([
            'progress_step' => 'queued',
            'progress_label' => 'Import wird gestartet…',
            'progress_percent' => 2,
        ]);

        $payload = [
            'job_id' => $job->id,
            'status_url' => route('admin.experts.crawl.status', [$expert, $job]),
            'process_url' => route('admin.experts.crawl.process', [$expert, $job]),
            'message' => 'Crawl gestartet',
        ];

        if ($wantsJson) {
            return response()->json($payload);
        }

        $this->crawlService->runForExpert($expert, $job);
        $job = $job->fresh();

        if ($job->status === 'failed') {
            return back()
                ->with('error', 'Crawl fehlgeschlagen'.($job->notes ? ': '.$job->notes : '.'))
                ->withFragment('crawl');
        }

        return back()
            ->with('success', $job->notes ?: 'Crawl abgeschlossen.')
            ->withFragment('crawl');
    }

    public function processCrawl(Expert $expert, CrawlJob $job): JsonResponse
    {
        if ((int) $job->expert_id !== (int) $expert->id) {
            abort(403);
        }

        if (in_array($job->status, ['completed', 'failed'], true)) {
            return response()->json([
                'ok' => true,
                'status' => $job->status,
                'message' => 'Bereits beendet.',
            ]);
        }

        if ($job->status === 'running') {
            return response()->json([
                'ok' => true,
                'status' => 'running',
                'message' => 'Läuft bereits.',
            ]);
        }

        ignore_user_abort(true);
        @set_time_limit(300);

        if (session()->isStarted()) {
            session()->save();
        }

        $this->crawlService->runForExpert($expert->fresh(), $job->fresh());

        return response()->json([
            'ok' => true,
            'status' => $job->fresh()->status,
        ]);
    }

    public function crawlStatus(Expert $expert, CrawlJob $job): JsonResponse
    {
        if ((int) $job->expert_id !== (int) $expert->id) {
            abort(403);
        }

        $job->loadCount(['results', 'errors']);

        return response()->json([
            'id' => $job->id,
            'status' => $job->status,
            'progress_step' => $job->progress_step,
            'progress_label' => $job->progress_label,
            'progress_percent' => (int) $job->progress_percent,
            'urls_discovered' => (int) $job->urls_discovered,
            'urls_processed' => (int) $job->urls_processed,
            'results_count' => (int) $job->results_count,
            'errors_count' => (int) $job->errors_count,
            'notes' => $job->notes,
            'finished' => in_array($job->status, ['completed', 'failed'], true),
        ]);
    }

    public function createArticle(Expert $expert): View
    {
        return view('admin.experts.articles.create', [
            'expert' => $expert,
            'categories' => ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get(),
            'article' => null,
        ]);
    }

    public function generateArticleAi(Request $request, Expert $expert): JsonResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'min:8', 'max:1000'],
            'tone' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'exists:article_categories,id'],
        ]);

        try {
            $article = $this->articleAi->generate(
                $expert,
                $validated['topic'],
                $validated['tone'] ?? null,
                isset($validated['category_id']) ? (int) $validated['category_id'] : null
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Artikel konnte nicht generiert werden.',
            ], 422);
        }

        return response()->json($article);
    }

    public function storeArticle(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $this->validateArticle($request);
        $categoryId = $validated['category_id']
            ?? ArticleCategory::query()->orderBy('sort_order')->value('id');

        if ($categoryId === null) {
            return back()->withInput()->with('error', 'Keine Artikelkategorie vorhanden.');
        }

        $data = [
            'expert_id' => $expert->id,
            'category_id' => $categoryId,
            'title' => $validated['title'],
            'slug' => $this->uniqueArticleSlug($validated['title']),
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
            'is_crawled' => false,
        ];

        if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
            $data['cover_image'] = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $article = Article::query()->create($data);
        $this->syncGallery($request, $article);

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Fachbeitrag für «'.$expert->company_name.'» veröffentlicht.')
            ->withFragment('artikel');
    }

    public function editArticle(Expert $expert, Article $article): View
    {
        $this->assertArticle($expert, $article);

        return view('admin.experts.articles.edit', [
            'expert' => $expert,
            'article' => $article->load('images'),
            'categories' => ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get(),
        ]);
    }

    public function updateArticle(Request $request, Expert $expert, Article $article): RedirectResponse
    {
        $this->assertArticle($expert, $article);
        $validated = $this->validateArticle($request);
        $action = $request->input('action', 'save');

        $data = [
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
        ];

        if ($action === 'publish') {
            $data['status'] = ArticleStatus::PUBLISHED;
            $data['published_at'] = $article->published_at ?: now();
        } elseif ($action === 'draft') {
            $data['status'] = ArticleStatus::DRAFT;
            $data['published_at'] = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($article->cover_image) {
                Storage::disk('public')->delete($article->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $article->update($data);
        $this->syncGallery($request, $article);

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Fachbeitrag gespeichert.')
            ->withFragment('artikel');
    }

    public function destroyArticle(Expert $expert, Article $article): RedirectResponse
    {
        $this->assertArticle($expert, $article);

        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }
        $article->delete();

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Fachbeitrag gelöscht.')
            ->withFragment('artikel');
    }

    public function createEvent(Expert $expert): View
    {
        return view('admin.experts.events.create', [
            'expert' => $expert,
            'event' => null,
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function generateEventAi(Request $request, Expert $expert): JsonResponse
    {
        $validated = $request->validate([
            'briefing' => ['required', 'string', 'min:8', 'max:2000'],
            'event_type' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $draft = $this->eventAi->generateDraft(
                $expert,
                $validated['briefing'],
                $validated['event_type'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Entwurf konnte nicht erzeugt werden.',
            ], 422);
        }

        return response()->json($draft);
    }

    public function storeEvent(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        Event::query()->create([
            ...$validated,
            'expert_id' => $expert->id,
            'slug' => $this->uniqueEventSlug($validated['title']),
            'status' => EventStatus::PUBLISHED,
            'published_at' => now(),
            'is_crawled' => false,
            'needs_review' => false,
        ]);

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Event für «'.$expert->company_name.'» veröffentlicht.')
            ->withFragment('events');
    }

    public function editEvent(Expert $expert, Event $event): View
    {
        $this->assertEvent($expert, $event);

        return view('admin.experts.events.edit', [
            'expert' => $expert,
            'event' => $event,
            'cantons' => Canton::query()->orderBy('name_de')->get(),
        ]);
    }

    public function updateEvent(Request $request, Expert $expert, Event $event): RedirectResponse
    {
        $this->assertEvent($expert, $event);
        $validated = $this->validateEvent($request);
        $action = $request->input('action', 'save');

        $payload = $validated;
        if ($action === 'publish') {
            $payload['status'] = EventStatus::PUBLISHED;
            $payload['needs_review'] = false;
            $payload['published_at'] = $event->published_at ?: now();
        } elseif ($action === 'draft') {
            $payload['status'] = EventStatus::DRAFT;
            $payload['published_at'] = null;
        }

        $event->update($payload);

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Event gespeichert.')
            ->withFragment('events');
    }

    public function destroyEvent(Expert $expert, Event $event): RedirectResponse
    {
        $this->assertEvent($expert, $event);
        $event->delete();

        return redirect()
            ->route('admin.experts.show', $expert)
            ->with('success', 'Event gelöscht.')
            ->withFragment('events');
    }

    /** @return array<string, mixed> */
    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:article_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'gallery_images' => ['nullable', 'array', 'max:8'],
            'gallery_images.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateEvent(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_type' => ['required', 'in:workshop,presentation,hearing,other'],
            'starts_on' => ['nullable', 'date'],
            'starts_at_time' => ['nullable', 'date_format:H:i'],
            'ends_on' => ['nullable', 'date'],
            'ends_at_time' => ['nullable', 'date_format:H:i'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'canton_id' => ['nullable', 'exists:cantons,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'price_label' => ['nullable', 'string', 'max:80'],
            'capacity_label' => ['nullable', 'string', 'max:80'],
            'registration_email' => ['nullable', 'email', 'max:255'],
            'is_top_event' => ['nullable', 'boolean'],
        ]);

        $data['is_top_event'] = $request->boolean('is_top_event');

        return $data;
    }

    private function syncGallery(Request $request, Article $article): void
    {
        $removeIds = collect($request->input('remove_images', []))->map(fn ($id) => (int) $id)->filter();

        if ($removeIds->isNotEmpty()) {
            $images = $article->images()->whereIn('id', $removeIds)->get();
            foreach ($images as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        if (! $request->hasFile('gallery_images')) {
            return;
        }

        $sort = (int) $article->images()->max('sort_order');
        Storage::disk('public')->makeDirectory('articles/gallery');

        foreach ($request->file('gallery_images') as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $sort++;
            $article->images()->create([
                'path' => $file->store('articles/gallery', 'public'),
                'sort_order' => $sort,
            ]);
        }
    }

    private function assertArticle(Expert $expert, Article $article): void
    {
        if ((int) $article->expert_id !== (int) $expert->id) {
            abort(404);
        }
    }

    private function assertEvent(Expert $expert, Event $event): void
    {
        if ((int) $event->expert_id !== (int) $expert->id) {
            abort(404);
        }
    }

    private function uniqueArticleSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : Str::random(8);
        $original = $slug;
        $counter = 1;
        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueEventSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : Str::random(8);
        $original = $slug;
        $counter = 1;
        while (Event::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
