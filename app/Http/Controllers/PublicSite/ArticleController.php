<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ArticleStatus;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->where('status', ArticleStatus::PUBLISHED)
            ->with(['category', 'expert'])
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('public.blog.index', [
            'articles' => $articles,
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
