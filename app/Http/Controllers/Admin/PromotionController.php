<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canton;
use App\Models\Promotion;
use App\Services\Invoice\InvoiceService;
use App\Services\Promotion\PromotionService;
use App\Support\InvoicePaymentStatus;
use App\Support\PromotionStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        private PromotionService $promotionService,
        private InvoiceService $invoiceService
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));
        $cantonId = $request->query('canton_id');

        $promotions = Promotion::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($cantonId, fn ($query) => $query->where('canton_id', $cantonId))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('expert', function ($expert) use ($q) {
                    $expert->where('company_name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->with(['expert', 'canton', 'invoice'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'status' => $status,
            'q' => $q,
            'cantonId' => $cantonId,
            'statuses' => PromotionStatus::all(),
            'cantons' => Canton::query()->orderBy('name_de')->get(['id', 'code', 'name_de']),
            'monthlyPrice' => $this->invoiceService->monthlyPromotionPrice(),
        ]);
    }

    public function activate(Promotion $promotion): RedirectResponse
    {
        $promotion->load('invoice');

        try {
            if ($promotion->invoice !== null && $promotion->invoice->payment_status === InvoicePaymentStatus::PAID) {
                $this->promotionService->activateAfterPayment($promotion, Carbon::now(), 1);
            } else {
                $startsAt = now();
                $promotion->update([
                    'status' => PromotionStatus::ACTIVE,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMonth(),
                ]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Aktivierung fehlgeschlagen: '.$e->getMessage());
        }

        return back()->with('success', 'Promotion aktiviert.');
    }

    public function expire(Promotion $promotion): RedirectResponse
    {
        $this->promotionService->expirePromotion($promotion);

        return back()->with('success', 'Promotion beendet.');
    }

    public function cancel(Promotion $promotion): RedirectResponse
    {
        if ($promotion->status === PromotionStatus::CANCELLED) {
            return back()->with('error', 'Promotion ist bereits storniert.');
        }

        $this->promotionService->cancelPromotion($promotion);

        return back()->with('success', 'Promotion storniert.');
    }

    public function createInvoice(Promotion $promotion): RedirectResponse
    {
        try {
            $invoice = $this->invoiceService->createForPromotion($promotion);
        } catch (\Throwable $e) {
            return back()->with('error', 'Rechnung fehlgeschlagen: '.$e->getMessage());
        }

        return back()->with(
            'success',
            'Rechnung '.$invoice->invoice_number.' erstellt ('.number_format((float) $invoice->amount, 2, '.', "'").' '.$invoice->currency.').'
        );
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,expire,cancel'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:promotions,id'],
        ]);

        $promotions = Promotion::query()->whereIn('id', $validated['ids'])->with('invoice')->get();
        $count = 0;
        $errors = 0;

        foreach ($promotions as $promotion) {
            try {
                switch ($validated['action']) {
                    case 'activate':
                        if ($promotion->status === PromotionStatus::ACTIVE) {
                            break;
                        }
                        if ($promotion->invoice !== null && $promotion->invoice->payment_status === InvoicePaymentStatus::PAID) {
                            $this->promotionService->activateAfterPayment($promotion, Carbon::now(), 1);
                        } else {
                            $startsAt = now();
                            $promotion->update([
                                'status' => PromotionStatus::ACTIVE,
                                'starts_at' => $startsAt,
                                'ends_at' => $startsAt->copy()->addMonth(),
                            ]);
                        }
                        $count++;
                        break;
                    case 'expire':
                        if ($promotion->status !== PromotionStatus::ACTIVE) {
                            break;
                        }
                        $this->promotionService->expirePromotion($promotion);
                        $count++;
                        break;
                    case 'cancel':
                        if ($promotion->status === PromotionStatus::CANCELLED) {
                            break;
                        }
                        $this->promotionService->cancelPromotion($promotion);
                        $count++;
                        break;
                }
            } catch (\Throwable) {
                $errors++;
            }
        }

        $msg = $count.' Promotion(en) aktualisiert.';
        if ($errors > 0) {
            $msg .= ' '.$errors.' fehlgeschlagen.';
        }

        return back()->with($errors > 0 ? 'error' : 'success', $msg);
    }
}
