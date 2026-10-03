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
        $count = 0;

        foreach ($this->crawlService->expertsDueForCrawl() as $expert) {
            CrawlExpertWebsiteJob::dispatch($expert);
            $count++;
        }

        return back()->with('success', $count.' Crawl-Job(s) in die Warteschlange gestellt.');
    }
}
