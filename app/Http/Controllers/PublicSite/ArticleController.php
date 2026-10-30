<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Services\Ranking\RankingService;
use App\Support\ArticleStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(private RankingService $rankings)
    {
    }

    public function index(Request $request): View
    {
        $categorySlug = trim((string) $request->query('gruppe', ''));
        $categories = ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get();
        $activeCategory = $categorySlug !== ''
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        $articles = Article::query()
            ->where('status', ArticleStatus::PUBLISHED)
            ->when($activeCategory, fn ($q) => $q->where('category_id', $activeCategory->id))
            ->with(['category', 'expert'])
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $topCards = collect();
        if ($activeCategory) {
            $topCards = $this->rankings->articleTop3((int) $activeCategory->id)
                ->filter(fn ($row) => $row->article !== null)
                ->map(fn ($row) => $row->article)
                ->values();
        }

        return view('public.blog.index', [
            'articles' => $articles,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'topCards' => $topCards,
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()
            ->where('slug', $slug)
            ->where('status', ArticleStatus::PUBLISHED)
            ->with(['category', 'expert', 'images'])
            ->firstOrFail();

        return view('public.blog.show', [
            'article' => $article,
        ]);
    }
}
