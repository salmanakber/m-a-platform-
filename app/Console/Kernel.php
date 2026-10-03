<?php

namespace App\Console;

use App\Jobs\CrawlExpertWebsiteJob;
use App\Jobs\ExpirePromotionsJob;
use App\Services\Crawl\CrawlService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new ExpirePromotionsJob)->daily();

        $schedule->call(function () {
            $crawlService = app(CrawlService::class);
            foreach ($crawlService->expertsDueForCrawl() as $expert) {
                CrawlExpertWebsiteJob::dispatch($expert);
            }
        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
