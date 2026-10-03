<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /** @var array<string, string> */
    private const LEGAL_PAGES = [
        'impressum' => 'Impressum',
        'datenschutz' => 'Datenschutz',
        'agb' => 'Allgemeine Geschäftsbedingungen',
    ];

    public function index(): View
    {
        $this->ensureLegalPagesExist();

        $pages = Page::query()->orderBy('slug')->get();

        return view('admin.pages.index', [
            'pages' => $pages,
        ]);
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $page->update([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Seite gespeichert.');
    }

    private function ensureLegalPagesExist(): void
    {
        foreach (self::LEGAL_PAGES as $slug => $title) {
            Page::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'body' => '',
                    'is_published' => true,
                ]
            );
        }
    }
}
