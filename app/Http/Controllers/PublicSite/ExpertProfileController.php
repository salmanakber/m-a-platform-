<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Support\ExpertStatus;
use Illuminate\View\View;

class ExpertProfileController extends Controller
{
    public function show(string $slug): View
    {
        $expert = Expert::query()
            ->where('slug', $slug)
            ->where('status', ExpertStatus::APPROVED)
            ->where('is_public', true)
            ->with(['offices.canton', 'media'])
            ->firstOrFail();

        $office = $expert->primaryOffice();

        $articles = $expert->articles()
            ->where('status', \App\Support\ArticleStatus::PUBLISHED)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        return view('public.experts.show', [
            'expert' => $expert,
            'office' => $office,
            'articles' => $articles,
            'offices' => $expert->offices,
            'media' => $expert->media,
        ]);
    }
}
