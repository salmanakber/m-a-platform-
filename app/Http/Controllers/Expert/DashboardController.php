<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CrawlJob;
use App\Models\ExpertOffice;
use App\Models\Lead;
use App\Models\Promotion;
use App\Support\LeadStatus;
use App\Support\PromotionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $expert = Auth::user()?->expert;
        $expertId = $expert?->id;

        $newLeads = $expertId
            ? Lead::query()->where('expert_id', $expertId)->where('status', LeadStatus::STATUS_NEW)->count()
            : 0;

        $totalLeads = $expertId
            ? Lead::query()->where('expert_id', $expertId)->count()
            : 0;

        $articleCount = $expertId
            ? Article::query()->where('expert_id', $expertId)->count()
            : 0;

        $activePromotions = $expertId
            ? Promotion::query()->where('expert_id', $expertId)->where('status', PromotionStatus::ACTIVE)->count()
            : 0;

        $pendingPromotions = $expertId
            ? Promotion::query()->where('expert_id', $expertId)->where('status', PromotionStatus::PENDING)->count()
            : 0;

        $recentLeads = $expertId
            ? Lead::query()->where('expert_id', $expertId)->orderByDesc('created_at')->limit(5)->get()
            : collect();

        $officeCount = $expertId
            ? ExpertOffice::query()->where('expert_id', $expertId)->count()
            : 0;

        $lastCrawl = $expertId
            ? CrawlJob::query()->where('expert_id', $expertId)->orderByDesc('created_at')->first()
            : null;

        $completeness = $this->profileCompleteness($expert, $officeCount);

        return view('expert.dashboard', [
            'expert' => $expert,
            'newLeads' => $newLeads,
            'totalLeads' => $totalLeads,
            'articleCount' => $articleCount,
            'activePromotions' => $activePromotions,
            'pendingPromotions' => $pendingPromotions,
            'recentLeads' => $recentLeads,
            'officeCount' => $officeCount,
            'lastCrawl' => $lastCrawl,
            'completeness' => $completeness,
        ]);
    }

    private function profileCompleteness($expert, int $officeCount): array
    {
        if ($expert === null) {
            return ['percent' => 0, 'done' => 0, 'total' => 8, 'missing' => ['Profil anlegen']];
        }

        $checks = [
            'Firmenname' => filled($expert->company_name),
            'E-Mail & Telefon' => filled($expert->email) && filled($expert->phone),
            'Website' => filled($expert->website),
            'Beschreibung' => filled($expert->description),
            'Leistungen' => filled($expert->services_text),
            'Logo' => filled($expert->logo_path),
            'Standort' => $officeCount > 0,
            'Buy/Sell Fokus' => (bool) $expert->offers_buy || (bool) $expert->offers_sell,
        ];

        $done = count(array_filter($checks));
        $total = count($checks);
        $missing = array_keys(array_filter($checks, fn ($ok) => ! $ok));

        return [
            'percent' => (int) round(($done / max($total, 1)) * 100),
            'done' => $done,
            'total' => $total,
            'missing' => $missing,
        ];
    }
}
