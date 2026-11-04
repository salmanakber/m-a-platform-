<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Services\Invoice\InvoiceService;
use App\Services\Promotion\PromotionService;
use App\Support\InvoicePaymentStatus;
use App\Support\ProfessionalGroup;
use App\Support\PromotionStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $recent = Invoice::query()
            ->with(['expert', 'product'])
            ->orderByDesc('created_at')
            ->limit(40)
            ->get()
            ->map(fn (Invoice $inv) => $this->invoiceService->toStudioArray($inv))
            ->values()
            ->all();

        $recentExpertIds = collect($recent)
            ->pluck('expert_id')
            ->filter()
            ->unique()
            ->take(8)
            ->values();

        $recentExperts = $recentExpertIds->isEmpty()
            ? []
            : Expert::query()
                ->whereIn('id', $recentExpertIds)
                ->get(['id', 'company_name', 'email', 'professional_group', 'contact_person_name', 'contact_person_last_name'])
                ->sortBy(fn (Expert $e) => $recentExpertIds->search($e->id))
                ->values()
                ->map(fn (Expert $e) => $this->expertPickArray($e))
                ->all();

        $boot = [
            'recent_experts' => $recentExperts,
            'expert_count' => Expert::query()->count(),
            'products' => InvoiceProduct::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (InvoiceProduct $p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'description' => $p->description,
                    'unit_price' => (float) $p->unit_price,
                    'unit_price_label' => number_format((float) $p->unit_price, 2, '.', "'"),
                    'currency' => $p->currency ?: 'CHF',
                    'billing_period' => $p->billing_period,
                ])->values()->all(),
            'invoices' => $recent,
            'monthly_price' => $this->invoiceService->monthlyPromotionPrice(),
            'default_vat_rate' => $this->invoiceService->defaultVatRate(),
            'initial_invoice_id' => $request->integer('invoice') ?: null,
            'urls' => [
                'store' => route('admin.invoices.store'),
                'show' => route('admin.invoices.show', ['invoice' => '__ID__']),
                'update' => route('admin.invoices.update', ['invoice' => '__ID__']),
                'confirm' => route('admin.invoices.send-confirmation', ['invoice' => '__ID__']),
                'send' => route('admin.invoices.send-email', ['invoice' => '__ID__']),
                'search_experts' => route('admin.invoices.search-experts'),
                'products' => route('admin.invoice-products.index'),
            ],
        ];

        return view('admin.invoices.index', ['boot' => $boot]);
    }

    public function searchExperts(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $limit = min(25, max(5, (int) $request->input('limit', 12)));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'ok' => true,
                'q' => $q,
                'experts' => [],
                'message' => 'Mindestens 2 Zeichen eingeben.',
            ]);
        }

        $like = '%'.$q.'%';
        $experts = Expert::query()
            ->select(['id', 'company_name', 'email', 'professional_group', 'contact_person_name', 'contact_person_last_name'])
            ->where(function ($query) use ($like, $q) {
                $query->where('company_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('contact_person_name', 'like', $like)
                    ->orWhere('contact_person_last_name', 'like', $like)
                    ->orWhere(DB::raw("CONCAT(COALESCE(contact_person_name,''),' ',COALESCE(contact_person_last_name,''))"), 'like', $like);
                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            })
            ->orderBy('company_name')
            ->limit($limit)
            ->get()
            ->map(fn (Expert $e) => $this->expertPickArray($e))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'q' => $q,
            'experts' => $experts,
            'count' => count($experts),
        ]);
    }

    /** @return array<string, mixed> */
    private function expertPickArray(Expert $expert): array
    {
        $contact = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));

        return [
            'id' => $expert->id,
            'company_name' => $expert->company_name,
            'email' => $expert->email,
            'contact_name' => $contact !== '' ? $contact : null,
            'group_label' => ProfessionalGroup::label($expert->professional_group ?? ProfessionalGroup::MA_SPECIALIST),
        ];
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.invoices.index');
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'invoice' => $this->invoiceService->toStudioArray($invoice),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
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

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Rechnungsentwurf erstellt.',
                'invoice' => $this->invoiceService->toStudioArray($invoice),
            ], 201);
        }

        return redirect()->route('admin.invoices.index', ['invoice' => $invoice->id]);
    }

    public function edit(Invoice $invoice): RedirectResponse
    {
        return redirect()->route('admin.invoices.index', ['invoice' => $invoice->id]);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'invoice_product_id' => ['nullable', 'exists:invoice_products,id'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_name' => ['nullable', 'string', 'max:255'],
            'billing_address_line' => ['nullable', 'string', 'max:255'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'service_title' => ['nullable', 'string', 'max:255'],
            'service_detail' => ['nullable', 'string', 'max:5000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'invoice_date' => ['nullable', 'date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $invoice = $this->invoiceService->updateDraft($invoice, $validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Gespeichert.',
                'invoice' => $this->invoiceService->toStudioArray($invoice),
            ]);
        }

        return back()->with('success', 'Rechnung gespeichert.');
    }

    public function sendConfirmation(Invoice $invoice): JsonResponse|RedirectResponse
    {
        $ok = $this->invoiceService->sendConfirmation($invoice);
        $to = $invoice->fresh()->billing_email ?: $invoice->expert?->email;

        if ($ok) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Bestätigung gesendet an '.$to,
                    'invoice' => $this->invoiceService->toStudioArray($invoice->fresh(['expert', 'product'])),
                ]);
            }

            return back()->with('success', 'Bestätigungs-E-Mail gesendet an '.$to.'.');
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['ok' => false, 'message' => 'Bestätigung fehlgeschlagen (Vorlage/Resend prüfen).'], 422);
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

    public function sendEmail(Request $request, Invoice $invoice): JsonResponse|RedirectResponse
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

        $to = $invoice->billing_email ?: $invoice->expert?->email;
        $msg = $type === 'paid'
            ? 'Zahlungsbestätigung an '.$to.' gesendet.'
            : 'Rechnung per E-Mail an '.$to.' gesendet.';

        if (! $ok) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'E-Mail fehlgeschlagen (Resend/Vorlage prüfen).'], 422);
            }

            return back()->with('error', 'E-Mail fehlgeschlagen. Bitte Resend-Einstellungen und Vorlagen prüfen.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'invoice' => $this->invoiceService->toStudioArray($invoice->fresh(['expert', 'product'])),
            ]);
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
