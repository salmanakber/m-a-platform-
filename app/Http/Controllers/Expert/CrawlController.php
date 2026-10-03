<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\CrawlJob;
use App\Services\Crawl\CrawlService;
use App\Support\ExpertStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CrawlController extends Controller
{
    public const COOLDOWN_SECONDS = 120;

    public function __construct(private CrawlService $crawlService)
    {
    }

    public function index(): View
    {
        $expert = Auth::user()?->expert;

        $jobs = $expert
            ? CrawlJob::query()
                ->where('expert_id', $expert->id)
                ->withCount(['results', 'errors'])
                ->orderByDesc('created_at')
                ->limit(12)
                ->get()
            : collect();

        $lastJob = $jobs->first();
        $completedCount = $expert
            ? CrawlJob::query()->where('expert_id', $expert->id)->where('status', 'completed')->count()
            : 0;
        $failedCount = $expert
            ? CrawlJob::query()->where('expert_id', $expert->id)->where('status', 'failed')->count()
            : 0;

        $hasPending = $expert
            ? CrawlJob::query()
                ->where('expert_id', $expert->id)
                ->whereIn('status', ['pending', 'running'])
                ->exists()
            : false;

        $cooldownUntil = $this->cooldownUntil($expert?->id);

        return view('expert.crawl.index', [
            'expert' => $expert,
            'jobs' => $jobs,
            'lastJob' => $lastJob,
            'completedCount' => $completedCount,
            'failedCount' => $failedCount,
            'hasPending' => $hasPending,
            'cooldownUntil' => $cooldownUntil,
            'cooldownSeconds' => self::COOLDOWN_SECONDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'website_crawl_url' => ['nullable', 'url', 'max:500'],
            'crawl_enabled' => ['nullable', 'boolean'],
        ]);

        $url = $validated['website_crawl_url'] ?? null;
        $enabled = $request->boolean('crawl_enabled');

        if ($enabled && blank($url)) {
            return back()->with('error', 'Bitte eine Website-Adresse hinterlegen, bevor Sie den Import aktivieren.');
        }

        $expert->update([
            'website_crawl_url' => $url,
            'crawl_enabled' => $enabled,
        ]);

        return back()->with('success', 'Einstellungen gespeichert.');
    }

    public function run(Request $request): RedirectResponse|JsonResponse
    {
        $expert = $this->requireExpert();
        $wantsJson = $request->expectsJson() || $request->ajax();

        if ($expert->status !== ExpertStatus::APPROVED) {
            $message = 'Der Website-Import ist erst verfügbar, wenn Ihr Profil freigegeben wurde.';

            return $wantsJson
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $validated = $request->validate([
            'website_crawl_url' => ['nullable', 'url', 'max:500'],
        ]);

        $url = $validated['website_crawl_url']
            ?? $expert->website_crawl_url
            ?? $expert->website;

        if (blank($url)) {
            $message = 'Bitte zuerst eine gültige Website-Adresse eingeben und speichern.';

            return $wantsJson
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        if ($this->hasActiveJob($expert->id)) {
            $active = CrawlJob::query()
                ->where('expert_id', $expert->id)
                ->whereIn('status', ['pending', 'running'])
                ->orderByDesc('id')
                ->first();

            return $wantsJson
                ? response()->json([
                    'message' => 'Ein Import läuft bereits.',
                    'job_id' => $active?->id,
                    'status_url' => $active ? route('expert.crawl.status', $active) : null,
                ], 409)
                : back()->with('error', 'Ein Import läuft bereits.');
        }

        $cooldownUntil = $this->cooldownUntil($expert->id);
        if ($cooldownUntil && $cooldownUntil->isFuture()) {
            $seconds = max(1, now()->diffInSeconds($cooldownUntil, false));
            $message = 'Bitte warten Sie noch '.$seconds.' Sekunden, bevor Sie erneut importieren.';

            return $wantsJson
                ? response()->json([
                    'message' => $message,
                    'cooldown_until' => $cooldownUntil->toIso8601String(),
                    'cooldown_seconds' => $seconds,
                ], 429)
                : back()->with('error', $message);
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

        // Return immediately — browser starts process + polls via AJAX (no page reload).
        $payload = [
            'job_id' => $job->id,
            'status_url' => route('expert.crawl.status', $job),
            'process_url' => route('expert.crawl.process', $job),
            'message' => 'Import gestartet',
            'cooldown_seconds' => self::COOLDOWN_SECONDS,
        ];

        if ($wantsJson) {
            return response()->json($payload);
        }

        // Non-AJAX fallback: run inline then redirect.
        $this->crawlService->runForExpert($expert, $job);
        $job = $job->fresh();

        if ($job->status === 'failed') {
            return back()->with('error', 'Import fehlgeschlagen'.($job->notes ? ': '.$job->notes : '.'));
        }

        return back()->with('success', $job->notes ?: 'Website-Import abgeschlossen.');
    }

    public function process(CrawlJob $job): JsonResponse
    {
        $expert = $this->requireExpert();

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

        // Release session lock so status polling is not blocked while this runs.
        if (session()->isStarted()) {
            session()->save();
        }

        $this->crawlService->runForExpert($expert->fresh(), $job->fresh());

        return response()->json([
            'ok' => true,
            'status' => $job->fresh()->status,
        ]);
    }

    public function status(CrawlJob $job): JsonResponse
    {
        $expert = $this->requireExpert();

        if ((int) $job->expert_id !== (int) $expert->id) {
            abort(403);
        }

        $job->loadCount(['results', 'errors']);
        $cooldownUntil = $this->cooldownUntil($expert->id);

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
            'cooldown_until' => $cooldownUntil?->toIso8601String(),
            'can_run' => ($cooldownUntil === null || $cooldownUntil->isPast())
                && ! $this->hasActiveJob($expert->id)
                && $expert->status === ExpertStatus::APPROVED,
        ]);
    }

    private function cooldownUntil(?int $expertId): ?\Illuminate\Support\Carbon
    {
        if (! $expertId) {
            return null;
        }

        $recent = CrawlJob::query()
            ->where('expert_id', $expertId)
            ->where('created_at', '>=', now()->subSeconds(self::COOLDOWN_SECONDS))
            ->whereIn('status', ['pending', 'running', 'completed'])
            ->orderByDesc('created_at')
            ->first();

        return $recent
            ? $recent->created_at->copy()->addSeconds(self::COOLDOWN_SECONDS)
            : null;
    }

    private function hasActiveJob(int $expertId): bool
    {
        return CrawlJob::query()
            ->where('expert_id', $expertId)
            ->whereIn('status', ['pending', 'running'])
            ->exists();
    }

    private function requireExpert()
    {
        $expert = Auth::user()?->expert;

        if ($expert === null) {
            abort(403);
        }

        return $expert;
    }
}
