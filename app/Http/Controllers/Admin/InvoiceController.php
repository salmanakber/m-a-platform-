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
            'send_email' => ['nullable', 'boolean'],
        ]);

        $wasPaid = $invoice->payment_status === InvoicePaymentStatus::PAID;
        $invoice->update(['payment_status' => $validated['payment_status']]);
        $nowPaid = $validated['payment_status'] === InvoicePaymentStatus::PAID;

        $msg = 'Zahlungsstatus aktualisiert.';

        if (
            ($validated['activate_promotion'] ?? false)
            && $nowPaid
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

        $shouldMail = $request->boolean('send_email') && $nowPaid && ! $wasPaid;
        if ($shouldMail) {
            if ($this->invoiceService->notifyPaid($invoice->fresh())) {
                $msg .= ' Zahlungsbestätigung per E-Mail gesendet.';
            } else {
                $msg .= ' E-Mail konnte nicht gesendet werden (Resend/Vorlage prüfen).';
            }
        }

        return back()->with('success', $msg);
    }

    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:created,paid,auto'],
        ]);

        $type = $validated['type'] ?? 'auto';
        if ($type === 'auto') {
            $type = $invoice->payment_status === InvoicePaymentStatus::PAID ? 'paid' : 'created';
        }

        $ok = $type === 'paid'
            ? $this->invoiceService->notifyPaid($invoice)
            : $this->invoiceService->notifyCreated($invoice);

        if (! $ok) {
            return back()->with('error', 'E-Mail fehlgeschlagen. Bitte Resend-Einstellungen und Vorlagen prüfen.');
        }

        return back()->with('success', $type === 'paid'
            ? 'Zahlungsbestätigung an '.$invoice->expert->email.' gesendet.'
            : 'Rechnung per E-Mail an '.$invoice->expert->email.' gesendet.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:'.implode(',', InvoicePaymentStatus::all())],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:invoices,id'],
            'activate_promotion' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $invoices = Invoice::query()
            ->whereIn('id', $validated['ids'])
            ->with(['expert', 'promotion', 'linkedPromotion'])
            ->get();

        $count = 0;
        $activated = 0;
        $mailed = 0;
        $sendEmail = $request->boolean('send_email');
        $markPaid = $validated['payment_status'] === InvoicePaymentStatus::PAID;

        foreach ($invoices as $invoice) {
            $wasPaid = $invoice->payment_status === InvoicePaymentStatus::PAID;
            $invoice->update(['payment_status' => $validated['payment_status']]);
            $count++;

            if (
                ($validated['activate_promotion'] ?? false)
                && $markPaid
            ) {
                $promotion = $invoice->linkedPromotion ?? $invoice->promotion;
                if ($promotion && $promotion->status !== PromotionStatus::ACTIVE) {
                    try {
                        $promotion->update(['invoice_id' => $invoice->id]);
                        $this->promotionService->activateAfterPayment($promotion->fresh('invoice'), Carbon::now(), 1);
                        $activated++;
                    } catch (\Throwable) {
                        // keep going
                    }
                }
            }

            if ($sendEmail && $markPaid && ! $wasPaid) {
                if ($this->invoiceService->notifyPaid($invoice->fresh())) {
                    $mailed++;
                }
            }
        }

        $msg = $count.' Rechnung(en) aktualisiert.';
        if ($activated > 0) {
            $msg .= ' '.$activated.' Promotion(en) aktiviert.';
        }
        if ($mailed > 0) {
            $msg .= ' '.$mailed.' E-Mail(s) gesendet.';
        }

        return back()->with('success', $msg);
    }
}
