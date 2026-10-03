<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function legalImpressum(): View
    {
        return $this->legal('impressum');
    }

    public function legalDatenschutz(): View
    {
        return $this->legal('datenschutz');
    }

    public function legalAgb(): View
    {
        return $this->legal('agb');
    }

    private function legal(string $slug): View
    {
        $defaults = [
            'impressum' => 'Impressum',
            'datenschutz' => 'Datenschutz',
            'agb' => 'Allgemeine Geschäftsbedingungen',
        ];

        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        return view('public.legal.show', [
            'title' => $page->title ?? $defaults[$slug],
            'body' => $page->body ?? null,
        ]);
    }
}
