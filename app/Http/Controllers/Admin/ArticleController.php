<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Expert;
use App\Support\ArticleStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));
        $expertId = $request->query('expert_id');

        $articles = Article::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($expertId, fn ($query) => $query->where('expert_id', $expertId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%'.$q.'%')
                        ->orWhere('excerpt', 'like', '%'.$q.'%');
                });
            })
            ->with(['expert', 'category'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles,
            'status' => $status,
            'q' => $q,
            'expertId' => $expertId,
            'statuses' => ArticleStatus::all(),
            'experts' => Expert::query()->orderBy('company_name')->get(['id', 'company_name']),
        ]);
    }

    public function edit(Article $article): View
    {
        $article->load(['expert', 'category']);

        return view('admin.articles.edit', [
            'article' => $article,
            'categories' => ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get(),
            'statuses' => ArticleStatus::all(),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:article_categories,id'],
            'status' => ['required', 'in:'.implode(',', ArticleStatus::all())],
        ]);

        $article->update($validated);

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Artikel gespeichert.');
    }

    public function block(Article $article): RedirectResponse
    {
        $article->update([
            'status' => ArticleStatus::BLOCKED,
            'blocked_at' => now(),
            'blocked_by' => Auth::id(),
        ]);

        return back()->with('success', 'Artikel gesperrt.');
    }

    public function unblock(Article $article): RedirectResponse
    {
        $article->update([
            'status' => ArticleStatus::PUBLISHED,
            'blocked_at' => null,
            'blocked_by' => null,
        ]);

        return back()->with('success', 'Artikel entsperrt.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Artikel gelöscht.');
    }
}
