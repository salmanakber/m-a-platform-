<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\ExpertMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $expert = Auth::user()?->expert;

        return view('expert.profile.edit', [
            'expert' => $expert,
            'media' => $expert?->media ?? collect(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $expert = Auth::user()?->expert;

        if ($expert === null) {
            abort(403);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string'],
            'services_text' => ['nullable', 'string'],
            'offers_buy' => ['nullable', 'boolean'],
            'offers_sell' => ['nullable', 'boolean'],
            'contact_person_name' => ['nullable', 'string', 'max:120'],
            'contact_person_last_name' => ['nullable', 'string', 'max:120'],
            'website_crawl_url' => ['nullable', 'url', 'max:500'],
            'crawl_enabled' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'gallery' => ['nullable', 'array', 'max:6'],
            'gallery.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
        ]);

        $data = [
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'services_text' => $validated['services_text'] ?? null,
            'offers_buy' => $request->boolean('offers_buy'),
            'offers_sell' => $request->boolean('offers_sell'),
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'contact_person_last_name' => $validated['contact_person_last_name'] ?? null,
            'website_crawl_url' => $validated['website_crawl_url'] ?? null,
            'crawl_enabled' => $request->boolean('crawl_enabled'),
        ];

        $logoUpdated = false;

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if ($expert->logo_path) {
                Storage::disk('public')->delete($expert->logo_path);
            }

            Storage::disk('public')->makeDirectory('experts/logos');
            $data['logo_path'] = $request->file('logo')->store('experts/logos', 'public');
            $logoUpdated = true;
        } elseif ($request->hasFile('logo')) {
            return back()
                ->withInput()
                ->with('error', 'Logo-Upload fehlgeschlagen. Bitte eine gültige Bilddatei (JPG, PNG, WebP, max. 4 MB) wählen.');
        }

        $expert->update($data);

        // Remove selected gallery images
        $removeIds = collect($request->input('remove_media', []))
            ->map(fn ($id) => (int) $id)
            ->filter();

        if ($removeIds->isNotEmpty()) {
            $toRemove = ExpertMedia::query()
                ->where('expert_id', $expert->id)
                ->whereIn('id', $removeIds)
                ->get();

            foreach ($toRemove as $media) {
                Storage::disk('public')->delete($media->path);
                $media->delete();
            }
        }

        // Optional gallery uploads (max 6 total)
        if ($request->hasFile('gallery')) {
            $currentCount = ExpertMedia::query()->where('expert_id', $expert->id)->count();
            $slots = max(0, 6 - $currentCount);
            $sort = (int) ExpertMedia::query()->where('expert_id', $expert->id)->max('sort_order');

            Storage::disk('public')->makeDirectory('experts/gallery');

            foreach (array_slice($request->file('gallery'), 0, $slots) as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $sort++;
                ExpertMedia::query()->create([
                    'expert_id' => $expert->id,
                    'path' => $file->store('experts/gallery', 'public'),
                    'sort_order' => $sort,
                ]);
            }
        }

        $message = $logoUpdated
            ? 'Profil und Medien gespeichert.'
            : 'Profil gespeichert.';

        return back()->with('success', $message);
    }
}
