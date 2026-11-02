<?php

namespace App\Services\Invoice;

use App\Models\Expert;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Promotion;
use App\Services\Email\NotificationService;
use App\Services\Settings\SettingsService;
use App\Support\InvoicePaymentStatus;
use App\Support\SettingKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        private SettingsService $settings,
        private NotificationService $notifications
    ) {
    }

    public function monthlyPromotionPrice(): float
    {
        return (float) $this->settings->get(SettingKey::PROMOTION_MONTHLY_PRICE_CHF, '0');
    }

    public function createForPromotion(Promotion $promotion, ?float $amount = null, string $currency = 'CHF', bool $notify = true): Invoice
    {
        $promotion->loadMissing(['expert', 'invoice', 'canton']);

        if ($promotion->invoice !== null) {
            return $promotion->invoice;
        }

        $amount = $amount ?? $this->monthlyPromotionPrice();

        $invoice = DB::transaction(function () use ($promotion, $amount, $currency) {
            $invoice = Invoice::query()->create([
                'expert_id' => $promotion->expert_id,
                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'amount' => $amount,
                'currency' => $currency,
                'payment_status' => InvoicePaymentStatus::PENDING,
                'notes' => sprintf(
                    'Promotion %s · Position %d',
                    $promotion->canton->name_de ?? ('Kanton #'.$promotion->canton_id),
                    $promotion->position_number
                ),
                'promotion_id' => $promotion->id,
            ]);

            $promotion->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });

        if ($notify) {
            $this->notifyCreated($invoice);
        }

        return $invoice;
    }

    /**
     * Create a draft invoice from a product (no e-mail yet).
     *
     * @param  array{amount?: float|null, billing_email?: string|null, service_title?: string|null, service_detail?: string|null, notes?: string|null, admin_notes?: string|null, currency?: string}  $overrides
     */
    public function createFromProduct(Expert $expert, InvoiceProduct $product, array $overrides = []): Invoice
    {
        $amount = array_key_exists('amount', $overrides) && $overrides['amount'] !== null
            ? (float) $overrides['amount']
            : (float) $product->unit_price;

        return Invoice::query()->create([
            'expert_id' => $expert->id,
            'invoice_product_id' => $product->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'amount' => $amount,
            'currency' => $overrides['currency'] ?? ($product->currency ?: 'CHF'),
            'payment_status' => InvoicePaymentStatus::PENDING,
            'billing_email' => $overrides['billing_email'] ?? $expert->email,
            'service_title' => $overrides['service_title'] ?? $product->name,
            'service_detail' => $overrides['service_detail'] ?? $product->description,
            'notes' => $overrides['notes'] ?? null,
            'admin_notes' => $overrides['admin_notes'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Invoice $invoice, array $data): Invoice
    {
        $invoice->update([
            'billing_email' => $data['billing_email'] ?? $invoice->billing_email,
            'service_title' => $data['service_title'] ?? $invoice->service_title,
            'service_detail' => $data['service_detail'] ?? $invoice->service_detail,
            'amount' => array_key_exists('amount', $data) ? $data['amount'] : $invoice->amount,
            'currency' => $data['currency'] ?? $invoice->currency,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $invoice->notes,
            'admin_notes' => array_key_exists('admin_notes', $data) ? $data['admin_notes'] : $invoice->admin_notes,
            'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
            'invoice_product_id' => $data['invoice_product_id'] ?? $invoice->invoice_product_id,
        ]);

        return $invoice->fresh(['expert', 'product']);
    }

    public function sendConfirmation(Invoice $invoice): bool
    {
        try {
            $this->notifications->sendInvoiceConfirmation($invoice->fresh(['expert', 'product', 'promotion.canton', 'linkedPromotion.canton']));
            $invoice->update(['confirmation_sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invoice confirmation e-mail failed: '.$e->getMessage(), ['invoice_id' => $invoice->id]);

            return false;
        }
    }

    public function notifyCreated(Invoice $invoice): bool
    {
        try {
            $this->notifications->sendInvoiceCreated($invoice->fresh(['expert', 'product', 'promotion.canton', 'linkedPromotion.canton']));
            $invoice->update(['invoice_sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invoice created e-mail failed: '.$e->getMessage(), ['invoice_id' => $invoice->id]);

            return false;
        }
    }

    public function notifyPaid(Invoice $invoice): bool
    {
        try {
            $this->notifications->sendInvoicePaid($invoice->fresh(['expert', 'promotion.canton', 'linkedPromotion.canton']));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invoice paid e-mail failed: '.$e->getMessage(), ['invoice_id' => $invoice->id]);

            return false;
        }
    }

    public function nextInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'NE-'.$year.'-';

        $latest = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    public function toStudioArray(Invoice $invoice): array
    {
        $invoice->loadMissing(['expert', 'product']);

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
            'invoice_date_label' => optional($invoice->invoice_date)->format('d.m.Y'),
            'amount' => (float) $invoice->amount,
            'amount_label' => number_format((float) $invoice->amount, 2, '.', "'"),
            'currency' => $invoice->currency ?: 'CHF',
            'payment_status' => $invoice->payment_status,
            'billing_email' => $invoice->billing_email,
            'service_title' => $invoice->service_title,
            'service_detail' => $invoice->service_detail,
            'notes' => $invoice->notes,
            'admin_notes' => $invoice->admin_notes,
            'invoice_product_id' => $invoice->invoice_product_id,
            'expert_id' => $invoice->expert_id,
            'expert_name' => $invoice->expert->company_name ?? '—',
            'expert_email' => $invoice->expert->email ?? null,
            'product_name' => $invoice->product->name ?? null,
            'confirmation_sent_at' => optional($invoice->confirmation_sent_at)->format('d.m.Y H:i'),
            'invoice_sent_at' => optional($invoice->invoice_sent_at)->format('d.m.Y H:i'),
            'updated_label' => optional($invoice->updated_at)->format('d.m.Y H:i'),
        ];
    }
}
