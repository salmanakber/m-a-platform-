<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use App\Services\Promotion\PromotionService;
use App\Support\InvoicePaymentStatus;
use App\Support\PromotionStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private PromotionService $promotionService
    ) {
    }

    public function index(Request $request): View
    {
        $paymentStatus = $request->query('payment_status');
        $q = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $invoices = Invoice::query()
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($dateFrom, fn ($query) => $query->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('invoice_date', '<=', $dateTo))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('invoice_number', 'like', '%'.$q.'%')
                        ->orWhereHas('expert', function ($expert) use ($q) {
                            $expert->where('company_name', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            })
            ->with(['expert', 'promotion.canton', 'linkedPromotion.canton'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'paymentStatus' => $paymentStatus,
            'q' => $q,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'paymentStatuses' => InvoicePaymentStatus::all(),
            'monthlyPrice' => $this->invoiceService->monthlyPromotionPrice(),
        ]);
    }

    public function updatePaymentStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:'.implode(',', InvoicePaymentStatus::all())],
            'activate_promotion' => ['nullable', 'boolean'],
        ]);

        $invoice->update(['payment_status' => $validated['payment_status']]);

        $msg = 'Zahlungsstatus aktualisiert.';

        if (
            ($validated['activate_promotion'] ?? false)
            && $validated['payment_status'] === InvoicePaymentStatus::PAID
        ) {
            $promotion = $invoice->linkedPromotion ?? $invoice->promotion;
            if ($promotion && $promotion->status !== PromotionStatus::ACTIVE) {
                try {
                    $promotion->update(['invoice_id' => $invoice->id]);
                    $this->promotionService->activateAfterPayment($promotion->fresh('invoice'), Carbon::now(), 1);
                    $msg .= ' Verknüpfte Promotion aktiviert.';
                } catch (\Throwable $e) {
                    return back()->with('error', $msg.' Promotion-Aktivierung fehlgeschlagen: '.$e->getMessage());
                }
            }
        }

        return back()->with('success', $msg);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:'.implode(',', InvoicePaymentStatus::all())],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:invoices,id'],
            'activate_promotion' => ['nullable', 'boolean'],
        ]);

        $invoices = Invoice::query()
            ->whereIn('id', $validated['ids'])
            ->with(['promotion', 'linkedPromotion'])
            ->get();

        $count = 0;
        $activated = 0;

        foreach ($invoices as $invoice) {
            $invoice->update(['payment_status' => $validated['payment_status']]);
            $count++;

            if (
                ($validated['activate_promotion'] ?? false)
                && $validated['payment_status'] === InvoicePaymentStatus::PAID
            ) {
                $promotion = $invoice->linkedPromotion ?? $invoice->promotion;
                if ($promotion && $promotion->status !== PromotionStatus::ACTIVE) {
                    try {
                        $promotion->update(['invoice_id' => $invoice->id]);
                        $this->promotionService->activateAfterPayment($promotion->fresh('invoice'), Carbon::now(), 1);
                        $activated++;
                    } catch (\Throwable) {
                        // keep going for other invoices
                    }
                }
            }
        }

        $msg = $count.' Rechnung(en) aktualisiert.';
        if ($activated > 0) {
            $msg .= ' '.$activated.' Promotion(en) aktiviert.';
        }

        return back()->with('success', $msg);
    }
}
