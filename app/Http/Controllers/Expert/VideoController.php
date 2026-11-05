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

        $freeUsed = $videos->contains(fn (ExpertVideo $v) => $v->is_complimentary);
        $extraProduct = InvoiceProduct::query()->where('sku', 'video-extra')->where('is_active', true)->first();

        return view('expert.videos.index', [
            'expert' => $expert,
            'videos' => $videos,
            'freeAvailable' => ! $freeUsed,
            'extraPrice' => $extraProduct ? (float) $extraProduct->unit_price : 45.0,
            'extraCurrency' => $extraProduct->currency ?? 'CHF',
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

        $isFree = ! $freeUsed;
        $sort = (int) ExpertVideo::query()->where('expert_id', $expert->id)->max('sort_order') + 1;

        if ($isFree) {
            ExpertVideo::query()->create([
                'expert_id' => $expert->id,
                'title' => $validated['title'] ?: 'Video '.($existing + 1),
                'url' => $validated['url'],
                'sort_order' => $sort,
                'is_complimentary' => true,
                'status' => ExpertVideo::STATUS_ACTIVE,
            ]);

            return redirect()
                ->route('expert.videos.index')
                ->with('success', 'Video gespeichert (1. Video kostenlos).');
        }

        $product = InvoiceProduct::query()->where('sku', 'video-extra')->where('is_active', true)->first();
        if ($product === null) {
            return back()->withInput()->with('error', 'Preisprodukt «Zusätzliches Experten-Video» fehlt. Bitte Admin kontaktieren.');
        }

        $invoice = $this->invoices->createFromProduct($expert, $product, [
            'service_title' => 'Zusätzliches Experten-Video',
            'service_detail' => $validated['title'] ?: $validated['url'],
            'billing_email' => $expert->billing_email ?: $expert->email,
            'billing_name' => $expert->billing_name ?: $expert->company_name,
            'billing_address_line' => $expert->billing_address_line,
            'billing_postal_code' => $expert->billing_postal_code,
            'billing_city' => $expert->billing_city,
        ]);

        ExpertVideo::query()->create([
            'expert_id' => $expert->id,
            'title' => $validated['title'] ?: 'Video '.($existing + 1),
            'url' => $validated['url'],
            'sort_order' => $sort,
            'is_complimentary' => false,
            'status' => ExpertVideo::STATUS_PENDING,
            'invoice_id' => $invoice->id,
        ]);

        // Notify admin / create draft — optional send later from studio
        return redirect()
            ->route('expert.videos.index')
            ->with('success', 'Weiteres Video erfasst. Es wird nach Zahlung freigeschaltet (Rechnung '.$invoice->invoice_number.').');
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
