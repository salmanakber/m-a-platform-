<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Expert;
use App\Support\ArticleStatus;
use App\Support\ExpertStatus;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $experts = Expert::query()
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->with('offices.canton')
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
