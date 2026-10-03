<?php

namespace App\Jobs;

use App\Models\Expert;
use App\Services\Crawl\CrawlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlExpertWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public Expert $expert)
    {
    }

    public function handle(CrawlService $crawlService): void
    {
        try {
            $crawlService->runForExpert($this->expert);
        } catch (\Throwable $e) {
            // One expert must not stop the daily crawl batch.
            Log::warning('Crawl failed for expert '.$this->expert->id.': '.$e->getMessage());
        }
    }
}
