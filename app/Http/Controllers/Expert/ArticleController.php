<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Services\Article\ArticleAiService;
use App\Support\ArticleStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(private ArticleAiService $articleAi)
    {
    }
    public function index(): View
    {
        $expert = Auth::user()?->expert;

        $articles = $expert
            ? Article::query()->where('expert_id', $expert->id)->with('category')->orderByDesc('created_at')->paginate(15)
            : Article::query()->whereRaw('1 = 0')->paginate(15);

        return view('expert.articles.index', [
            'articles' => $articles,
        ]);
    }

    public function create(): View
    {
        return view('expert.articles.create', [
            'categories' => ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get(),
        ]);
    }

    public function generateAi(Request $request): JsonResponse
    {
        $expert = $this->requireExpert();

        $validated = $request->validate([
            'topic' => ['required', 'string', 'min:8', 'max:1000'],
            'tone' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'exists:article_categories,id'],
        ]);

        try {
            $article = $this->articleAi->generate(
                $expert,
                $validated['topic'],
                $validated['tone'] ?? null,
                isset($validated['category_id']) ? (int) $validated['category_id'] : null
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Artikel konnte nicht generiert werden.',
            ], 422);
        }

        return response()->json($article);
    }

    public function store(Request $request): RedirectResponse
    {
        $expert = $this->requireExpert();

        $validated = $this->validateArticle($request);

        $categoryId = $validated['category_id']
            ?? ArticleCategory::query()->orderBy('sort_order')->value('id');

        if ($categoryId === null) {
            return back()->withInput()->with('error', 'Keine Artikelkategorie vorhanden. Bitte Admin kontaktieren.');
        }

        $slug = $this->uniqueSlug($validated['title']);

        $data = [
            'expert_id' => $expert->id,
            'category_id' => $categoryId,
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
            'is_crawled' => false,
        ];

        if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
            $data['cover_image'] = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $article = Article::query()->create($data);
        $this->syncGallery($request, $article);

        return redirect()
            ->route('expert.articles.edit', $article)
            ->with('success', 'Artikel veröffentlicht.');
    }

    public function edit(Article $article): View
    {
        $this->authorizeArticle($article);

        return view('expert.articles.edit', [
            'article' => $article->load('images'),
            'categories' => ArticleCategory::query()->orderBy('sort_order')->orderBy('name_de')->get(),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->authorizeArticle($article);

        $validated = $this->validateArticle($request);

        $data = [
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
        ];

        if ($request->hasFile('cover_image')) {
            if ($article->cover_image) {
                Storage::disk('public')->delete($article->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $article->update($data);
        $this->syncGallery($request, $article);

        return back()->with('success', 'Artikel gespeichert.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorizeArticle($article);

        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }

        $article->delete();

        return redirect()
            ->route('expert.articles.index')
            ->with('success', 'Artikel gelöscht.');
    }

    private function requireExpert()
    {
        $expert = Auth::user()?->expert;

        if ($expert === null) {
            abort(403);
        }

        return $expert;
    }

    private function authorizeArticle(Article $article): void
    {
        $expert = Auth::user()?->expert;

        if ($expert === null || (int) $article->expert_id !== (int) $expert->id) {
            abort(403);
        }
    }

    /** @return array<string, mixed> */
    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:article_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'gallery_images' => ['nullable', 'array', 'max:8'],
            'gallery_images.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ]);
    }

    private function syncGallery(Request $request, Article $article): void
    {
        $removeIds = collect($request->input('remove_images', []))->map(fn ($id) => (int) $id)->filter();

        if ($removeIds->isNotEmpty()) {
            $images = $article->images()->whereIn('id', $removeIds)->get();
            foreach ($images as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }

        if (! $request->hasFile('gallery_images')) {
            return;
        }

        $sort = (int) $article->images()->max('sort_order');
        Storage::disk('public')->makeDirectory('articles/gallery');

        foreach ($request->file('gallery_images') as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $sort++;
            $article->images()->create([
                'path' => $file->store('articles/gallery', 'public'),
                'sort_order' => $sort,
            ]);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : Str::random(8);
        $original = $slug;
        $counter = 1;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
