<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CrawlExpertWebsiteJob;
use App\Models\CrawlJob;
use App\Services\Crawl\CrawlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CrawlController extends Controller
{
    public function __construct(private CrawlService $crawlService)
    {
    }

    public function index(): View
    {
        $jobs = CrawlJob::query()
            ->with('expert')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.crawl.index', [
            'jobs' => $jobs,
        ]);
    }

    public function runDaily(): RedirectResponse
    {
        $experts = $this->crawlService->expertsDueForCrawl();

        if ($experts->isEmpty()) {
            return back()->withErrors([
                'crawl' => 'Keine Experten bereit für den Crawl. Voraussetzung: Status freigegeben, «Website-Crawl aktiv» und eine Crawl-URL am Profil.',
            ]);
        }

        $count = 0;
        // Admin «jetzt starten» must run immediately — queued jobs often sit forever without a worker.
        foreach ($experts as $expert) {
            CrawlExpertWebsiteJob::dispatchSync($expert);
            $count++;
        }

        return back()->with(
            'success',
            $count.' Crawl(s) ausgeführt. Neue Blog-Entwürfe erscheinen bei den Experten (Status «Entwurf») und müssen freigegeben/veröffentlicht werden.'
        );
    }
}
