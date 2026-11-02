<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
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
                        ->orWhere('billing_email', 'like', '%'.$q.'%')
                        ->orWhere('service_title', 'like', '%'.$q.'%')
                        ->orWhereHas('expert', function ($expert) use ($q) {
                            $expert->where('company_name', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            })
            ->with(['expert', 'product', 'promotion.canton', 'linkedPromotion.canton'])
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

    public function create(): View
    {
        return view('admin.invoices.create', [
            'experts' => Expert::query()->orderBy('company_name')->get(['id', 'company_name', 'email']),
            'products' => InvoiceProduct::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expert_id' => ['required', 'exists:experts,id'],
            'invoice_product_id' => ['required', 'exists:invoice_products,id'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'service_title' => ['nullable', 'string', 'max:255'],
            'service_detail' => ['nullable', 'string', 'max:5000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $expert = Expert::query()->findOrFail($validated['expert_id']);
        $product = InvoiceProduct::query()->findOrFail($validated['invoice_product_id']);

        $invoice = $this->invoiceService->createFromProduct($expert, $product, [
            'billing_email' => $validated['billing_email'] ?? $expert->email,
            'service_title' => $validated['service_title'] ?? $product->name,
            'service_detail' => $validated['service_detail'] ?? $product->description,
            'amount' => array_key_exists('amount', $validated) && $validated['amount'] !== null
                ? (float) $validated['amount']
                : null,
            'notes' => $validated['notes'] ?? null,
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.invoices.edit', $invoice)
            ->with('success', 'Rechnung als Entwurf erstellt. Bitte prüfen, Bestätigung senden, danach Rechnung versenden.');
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load(['expert', 'product']);

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'products' => InvoiceProduct::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_product_id' => ['nullable', 'exists:invoice_products,id'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'service_title' => ['nullable', 'string', 'max:255'],
            'service_detail' => ['nullable', 'string', 'max:5000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'invoice_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->invoiceService->updateDraft($invoice, $validated);

        return back()->with('success', 'Rechnung gespeichert.');
    }

    public function sendConfirmation(Invoice $invoice): RedirectResponse
    {
        if ($this->invoiceService->sendConfirmation($invoice)) {
            return back()->with('success', 'Bestätigungs-E-Mail gesendet an '.($invoice->billing_email ?: $invoice->expert?->email).'.');
        }

        return back()->with('error', 'Bestätigungs-E-Mail fehlgeschlagen. Vorlage invoice_confirmation und Resend prüfen.');
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

        $to = $invoice->billing_email ?: $invoice->expert?->email;

        return back()->with('success', $type === 'paid'
            ? 'Zahlungsbestätigung an '.$to.' gesendet.'
            : 'Rechnung per E-Mail an '.$to.' gesendet.');
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
