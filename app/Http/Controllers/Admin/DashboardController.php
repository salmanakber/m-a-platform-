<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use App\Models\Article;
use App\Models\CrawlJob;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Promotion;
use App\Support\ArticleStatus;
use App\Support\ExpertStatus;
use App\Support\InvoicePaymentStatus;
use App\Support\LeadStatus;
use App\Support\PromotionStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'pendingExperts' => Expert::query()->where('status', ExpertStatus::PENDING)->count(),
            'approvedExperts' => Expert::query()->where('status', ExpertStatus::APPROVED)->count(),
            'disabledExperts' => Expert::query()->where('status', ExpertStatus::DISABLED)->count(),
            'openLeads' => Lead::query()->where('status', LeadStatus::STATUS_NEW)->count(),
            'totalLeads' => Lead::query()->count(),
            'activePromotions' => Promotion::query()->where('status', PromotionStatus::ACTIVE)->count(),
            'pendingPromotions' => Promotion::query()->where('status', PromotionStatus::PENDING)->count(),
            'publishedArticles' => Article::query()->where('status', ArticleStatus::PUBLISHED)->count(),
            'blockedArticles' => Article::query()->where('status', ArticleStatus::BLOCKED)->count(),
            'unpaidInvoices' => Invoice::query()->where('payment_status', InvoicePaymentStatus::PENDING)->count(),
            'pendingAiSuggestions' => AiSuggestion::query()->where('status', 'pending')->count(),
            'crawlJobsToday' => CrawlJob::query()->whereDate('created_at', today())->count(),
            'recentPendingExperts' => Expert::query()
                ->where('status', ExpertStatus::PENDING)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentLeads' => Lead::query()
                ->with('expert')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
