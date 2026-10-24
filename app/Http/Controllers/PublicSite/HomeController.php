<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Expert;
use App\Models\Promotion;
use App\Support\ArticleStatus;
use App\Support\ExpertStatus;
use App\Support\PromotionStatus;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $promotedIds = Promotion::query()
            ->where('status', PromotionStatus::ACTIVE)
            ->pluck('expert_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $expertsQuery = Expert::query()
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->with('offices.canton');

        if (count($promotedIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($promotedIds), '?'));
            $expertsQuery->orderByRaw('CASE WHEN id IN ('.$placeholders.') THEN 0 ELSE 1 END', $promotedIds);
        }

        $experts = $expertsQuery
            ->orderBy('company_name')
            ->limit(6)
            ->get();

        $articles = Article::query()
            ->where('status', ArticleStatus::PUBLISHED)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('public.home', [
            'experts' => $experts,
            'articles' => $articles,
        ]);
    }
}
