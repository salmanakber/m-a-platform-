<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\ExpertVideo;
use App\Models\InvoiceProduct;
use App\Services\Invoice\InvoiceService;
use App\Support\UrlNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    public function index(): View
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        $this->syncLegacyProfileVideo($expert);

        $videos = ExpertVideo::query()
            ->where('expert_id', $expert->id)
            ->with('invoice')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $count = $videos->count();
        $nextCount = $count + 1;
        $nextTier = $this->tierForCount($nextCount);
        $currentTier = $this->tierForCount(max(1, $count));
        $freeAvailable = ! $videos->contains(fn (ExpertVideo $v) => $v->is_complimentary);

        return view('expert.videos.index', [
            'expert' => $expert,
            'videos' => $videos,
            'freeAvailable' => $freeAvailable,
            'videoCount' => $count,
            'currentTier' => $currentTier,
            'nextTier' => $nextTier,
            'tiers' => [
                ['label' => '1. Video', 'price' => 0, 'hint' => 'kostenlos'],
                ['label' => 'bis 5 Videos', 'price' => 30, 'hint' => 'CHF / Mt.'],
                ['label' => 'bis 10 Videos', 'price' => 50, 'hint' => 'CHF / Mt.'],
                ['label' => 'über 10 Videos', 'price' => 150, 'hint' => 'CHF / Mt.'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        UrlNormalizer::mergeRequest($request, ['url']);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
        ]);

        $existing = ExpertVideo::query()->where('expert_id', $expert->id)->count();
        $freeUsed = ExpertVideo::query()
            ->where('expert_id', $expert->id)
            ->where('is_complimentary', true)
            ->exists();

        $sort = (int) ExpertVideo::query()->where('expert_id', $expert->id)->max('sort_order') + 1;
        $nextCount = $existing + 1;

        if (! $freeUsed) {
            ExpertVideo::query()->create([
                'expert_id' => $expert->id,
                'title' => $validated['title'] ?: 'Video '.$nextCount,
                'url' => $validated['url'],
                'sort_order' => $sort,
                'is_complimentary' => true,
                'status' => ExpertVideo::STATUS_ACTIVE,
            ]);

            return redirect()
                ->route('expert.videos.index')
                ->with('success', 'Video gespeichert (1. Video kostenlos — gilt auch für Juristen & Treuhand).');
        }

        $tier = $this->tierForCount($nextCount);
        $product = InvoiceProduct::query()->where('sku', $tier['sku'])->where('is_active', true)->first();
        if ($product === null) {
            return back()->withInput()->with('error', 'Video-Paket «'.$tier['label'].'» fehlt. Bitte Admin kontaktieren.');
        }

        // Only create a new invoice when entering / upgrading a paid tier.
        $needsNewInvoice = $this->crossesTierBoundary($existing, $nextCount);

        $invoice = null;
        if ($needsNewInvoice) {
            $invoice = $this->invoices->createFromProduct($expert, $product, [
                'service_title' => $product->name,
                'service_detail' => 'Video-Kontingent: '.$tier['label'].' · '.($validated['title'] ?: $validated['url']),
                'billing_email' => $expert->billing_email ?: $expert->email,
                'billing_name' => $expert->billing_name ?: $expert->company_name,
                'billing_address_line' => $expert->billing_address_line,
                'billing_postal_code' => $expert->billing_postal_code,
                'billing_city' => $expert->billing_city,
            ]);
        }

        ExpertVideo::query()->create([
            'expert_id' => $expert->id,
            'title' => $validated['title'] ?: 'Video '.$nextCount,
            'url' => $validated['url'],
            'sort_order' => $sort,
            'is_complimentary' => false,
            'status' => $needsNewInvoice ? ExpertVideo::STATUS_PENDING : ExpertVideo::STATUS_ACTIVE,
            'invoice_id' => $invoice?->id,
        ]);

        if ($needsNewInvoice && $invoice) {
            return redirect()
                ->route('expert.videos.index')
                ->with('success', 'Video erfasst. Paket «'.$tier['label'].'» (CHF '.number_format($tier['price'], 0).'/Mt.) — Rechnung '.$invoice->invoice_number.'.');
        }

        return redirect()
            ->route('expert.videos.index')
            ->with('success', 'Video gespeichert (bereits im aktuellen Paket «'.$tier['label'].'» enthalten).');
    }

    public function update(Request $request, ExpertVideo $video): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $video->expert_id !== (int) $expert->id, 403);

        UrlNormalizer::mergeRequest($request, ['url']);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
        ]);

        $video->update([
            'title' => $validated['title'] ?: $video->title,
            'url' => $validated['url'],
        ]);

        return back()->with('success', 'Video aktualisiert.');
    }

    public function destroy(ExpertVideo $video): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $video->expert_id !== (int) $expert->id, 403);

        $video->delete();

        return back()->with('success', 'Video entfernt.');
    }

    /** @return array{sku: string, label: string, price: float} */
    private function tierForCount(int $count): array
    {
        if ($count <= 1) {
            return ['sku' => '', 'label' => '1. Video gratis', 'price' => 0.0];
        }
        if ($count <= 5) {
            return ['sku' => 'video-pack-5', 'label' => 'bis 5 Videos', 'price' => 30.0];
        }
        if ($count <= 10) {
            return ['sku' => 'video-pack-10', 'label' => 'bis 10 Videos', 'price' => 50.0];
        }

        return ['sku' => 'video-pack-over10', 'label' => 'über 10 Videos', 'price' => 150.0];
    }

    private function crossesTierBoundary(int $before, int $after): bool
    {
        return $this->tierForCount($before)['sku'] !== $this->tierForCount($after)['sku']
            && $this->tierForCount($after)['price'] > 0;
    }

    private function syncLegacyProfileVideo($expert): void
    {
        if (! filled($expert->video_url)) {
            return;
        }

        $exists = ExpertVideo::query()->where('expert_id', $expert->id)->exists();
        if ($exists) {
            return;
        }

        ExpertVideo::query()->create([
            'expert_id' => $expert->id,
            'title' => 'Profil-Video',
            'url' => UrlNormalizer::normalize($expert->video_url) ?: $expert->video_url,
            'sort_order' => 1,
            'is_complimentary' => true,
            'status' => ExpertVideo::STATUS_ACTIVE,
        ]);
    }
}
