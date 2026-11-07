<?php

namespace App\Services\Invoice;

use App\Models\Expert;
use App\Models\ExpertVideo;
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

    public function defaultVatRate(): ?float
    {
        $raw = $this->settings->get(SettingKey::INVOICE_DEFAULT_VAT_RATE);
        if ($raw === null || $raw === '') {
            return null;
        }

        return (float) $raw;
    }

    /** @return array<string, string|null> */
    public function senderSettings(): array
    {
        return [
            'sender_name' => $this->settings->get(SettingKey::INVOICE_SENDER_NAME, 'nachfolge-experten.ch'),
            'sender_company' => $this->settings->get(SettingKey::INVOICE_SENDER_COMPANY, 'KMU Beratungen GmbH'),
            'sender_address' => $this->settings->get(
                SettingKey::INVOICE_SENDER_ADDRESS,
                "Brünigstrasse 144\n6060 Sarnen · Schweiz"
            ),
            'bank_name' => $this->settings->get(SettingKey::INVOICE_BANK_NAME),
            'bank_iban' => $this->settings->get(SettingKey::INVOICE_BANK_IBAN),
            'bank_bic' => $this->settings->get(SettingKey::INVOICE_BANK_BIC),
            'payment_hint' => $this->settings->get(
                SettingKey::INVOICE_PAYMENT_HINT,
                'Bitte überweisen Sie den Betrag unter Angabe der Rechnungsnummer.'
            ),
            'vat_uid' => $this->settings->get(SettingKey::INVOICE_VAT_UID),
            'default_vat_rate' => $this->settings->get(SettingKey::INVOICE_DEFAULT_VAT_RATE),
        ];
    }

    public function createForPromotion(Promotion $promotion, ?float $amount = null, string $currency = 'CHF', bool $notify = true): Invoice
    {
        $promotion->loadMissing(['expert', 'invoice', 'canton', 'articleCategory']);

        if ($promotion->invoice !== null) {
            return $promotion->invoice;
        }

        if ($amount === null) {
            $priceRow = \App\Models\PromotionPrice::query()
                ->where('scope', $promotion->scope ?: \App\Support\RankingScope::CANTON_TOP3)
                ->where('position', $promotion->position_number)
                ->when(
                    $promotion->professional_group,
                    fn ($q) => $q->where('professional_group', $promotion->professional_group),
                    fn ($q) => $q->whereNull('professional_group')
                )
                ->first();

            $amount = $priceRow ? (float) $priceRow->price_chf : $this->monthlyPromotionPrice();
        }

        $expert = $promotion->expert;

        $invoice = DB::transaction(function () use ($promotion, $amount, $currency, $expert) {
            $invoice = Invoice::query()->create([
                'expert_id' => $promotion->expert_id,
                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'amount' => $amount,
                'quantity' => 1,
                'unit_price' => $amount,
                'vat_rate' => $this->defaultVatRate(),
                'currency' => $currency,
                'payment_status' => InvoicePaymentStatus::PENDING,
                'billing_email' => $expert?->billing_email ?: $expert?->email,
                'billing_name' => $expert?->billing_name ?: $expert?->company_name,
                'billing_address_line' => $expert?->billing_address_line,
                'billing_postal_code' => $expert?->billing_postal_code,
                'billing_city' => $expert?->billing_city,
                'notes' => trim(sprintf(
                    'Promotion %s%s%s · Position %d',
                    $promotion->scopeLabel(),
                    $promotion->canton ? ' · '.$promotion->canton->name_de : '',
                    $promotion->articleCategory ? ' · '.$promotion->articleCategory->name_de : '',
                    $promotion->position_number
                )),
                'promotion_id' => $promotion->id,
            ]);

            $this->recalculateAmount($invoice);
            $promotion->update(['invoice_id' => $invoice->id]);

            return $invoice->fresh();
        });

        if ($notify) {
            $this->notifyCreated($invoice);
        }

        return $invoice;
    }

    /**
     * Create a draft invoice from a product (no e-mail yet).
     *
     * @param  array{amount?: float|null, billing_email?: string|null, service_title?: string|null, service_detail?: string|null, notes?: string|null, admin_notes?: string|null, currency?: string, quantity?: float|null, unit_price?: float|null, vat_rate?: float|null, period_start?: string|null, period_end?: string|null}  $overrides
     */
    public function createFromProduct(Expert $expert, InvoiceProduct $product, array $overrides = []): Invoice
    {
        $unitPrice = array_key_exists('unit_price', $overrides) && $overrides['unit_price'] !== null
            ? (float) $overrides['unit_price']
            : (float) $product->unit_price;
        $quantity = array_key_exists('quantity', $overrides) && $overrides['quantity'] !== null
            ? (float) $overrides['quantity']
            : 1.0;
        $vatRate = array_key_exists('vat_rate', $overrides)
            ? $overrides['vat_rate']
            : $this->defaultVatRate();

        $invoice = Invoice::query()->create([
            'expert_id' => $expert->id,
            'invoice_product_id' => $product->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'period_start' => $overrides['period_start'] ?? null,
            'period_end' => $overrides['period_end'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'vat_rate' => $vatRate,
            'amount' => 0,
            'currency' => $overrides['currency'] ?? ($product->currency ?: 'CHF'),
            'payment_status' => InvoicePaymentStatus::PENDING,
            'billing_email' => $overrides['billing_email']
                ?? ($expert->billing_email ?: $expert->email),
            'billing_name' => $overrides['billing_name']
                ?? ($expert->billing_name ?: $expert->company_name),
            'billing_address_line' => $overrides['billing_address_line'] ?? $expert->billing_address_line,
            'billing_postal_code' => $overrides['billing_postal_code'] ?? $expert->billing_postal_code,
            'billing_city' => $overrides['billing_city'] ?? $expert->billing_city,
            'service_title' => $overrides['service_title'] ?? $product->name,
            'service_detail' => $overrides['service_detail'] ?? $product->description,
            'notes' => $overrides['notes'] ?? null,
            'admin_notes' => $overrides['admin_notes'] ?? null,
        ]);

        if (array_key_exists('amount', $overrides) && $overrides['amount'] !== null) {
            $invoice->update(['amount' => (float) $overrides['amount']]);
        } else {
            $this->recalculateAmount($invoice);
        }

        return $invoice->fresh(['expert', 'product']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Invoice $invoice, array $data): Invoice
    {
        $invoice->fill([
            'billing_email' => $data['billing_email'] ?? $invoice->billing_email,
            'billing_name' => array_key_exists('billing_name', $data) ? $data['billing_name'] : $invoice->billing_name,
            'billing_address_line' => array_key_exists('billing_address_line', $data) ? $data['billing_address_line'] : $invoice->billing_address_line,
            'billing_postal_code' => array_key_exists('billing_postal_code', $data) ? $data['billing_postal_code'] : $invoice->billing_postal_code,
            'billing_city' => array_key_exists('billing_city', $data) ? $data['billing_city'] : $invoice->billing_city,
            'service_title' => $data['service_title'] ?? $invoice->service_title,
            'service_detail' => $data['service_detail'] ?? $invoice->service_detail,
            'currency' => $data['currency'] ?? $invoice->currency,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $invoice->notes,
            'admin_notes' => array_key_exists('admin_notes', $data) ? $data['admin_notes'] : $invoice->admin_notes,
            'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
            'period_start' => array_key_exists('period_start', $data) ? $data['period_start'] : $invoice->period_start,
            'period_end' => array_key_exists('period_end', $data) ? $data['period_end'] : $invoice->period_end,
            'invoice_product_id' => $data['invoice_product_id'] ?? $invoice->invoice_product_id,
        ]);

        if (array_key_exists('quantity', $data) && $data['quantity'] !== null && $data['quantity'] !== '') {
            $invoice->quantity = (float) $data['quantity'];
        }
        if (array_key_exists('unit_price', $data) && $data['unit_price'] !== null && $data['unit_price'] !== '') {
            $invoice->unit_price = (float) $data['unit_price'];
        }
        if (array_key_exists('vat_rate', $data)) {
            $invoice->vat_rate = ($data['vat_rate'] === null || $data['vat_rate'] === '')
                ? null
                : (float) $data['vat_rate'];
        }

        $invoice->save();

        if (array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
            && ! array_key_exists('quantity', $data) && ! array_key_exists('unit_price', $data)) {
            $invoice->update(['amount' => (float) $data['amount']]);
        } else {
            $this->recalculateAmount($invoice);
        }

        return $invoice->fresh(['expert', 'product']);
    }

    public function recalculateAmount(Invoice $invoice): Invoice
    {
        if ($invoice->unit_price === null) {
            $invoice->unit_price = (float) $invoice->amount;
        }
        if (! $invoice->quantity || (float) $invoice->quantity <= 0) {
            $invoice->quantity = 1;
        }

        $invoice->amount = $invoice->grossAmount();
        $invoice->save();

        return $invoice;
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
        $this->activateVideosForInvoice($invoice);

        try {
            $this->notifications->sendInvoicePaid($invoice->fresh(['expert', 'promotion.canton', 'linkedPromotion.canton']));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invoice paid e-mail failed: '.$e->getMessage(), ['invoice_id' => $invoice->id]);

            return false;
        }
    }

    /** Unlock pending expert videos linked to this invoice (also safe to call without e-mail). */
    public function activateVideosForInvoice(Invoice $invoice): int
    {
        return ExpertVideo::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', ExpertVideo::STATUS_PENDING)
            ->update(['status' => ExpertVideo::STATUS_ACTIVE]);
    }

    public function nextInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->format('Y');
            $prefix = 'NE-'.$year.'-';

            $latest = Invoice::query()
                ->where('invoice_number', 'like', $prefix.'%')
                ->orderByDesc('invoice_number')
                ->lockForUpdate()
                ->value('invoice_number');

            $seq = 1;
            if (is_string($latest) && preg_match('/(\d+)$/', $latest, $m)) {
                $seq = ((int) $m[1]) + 1;
            }

            return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
        });
    }

    /** @return array<string, mixed> */
    public function toStudioArray(Invoice $invoice): array
    {
        $invoice->loadMissing(['expert', 'product']);
        $net = $invoice->netAmount();
        $vat = $invoice->vatAmount();
        $gross = $invoice->grossAmount();

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
            'invoice_date_label' => optional($invoice->invoice_date)->format('d.m.Y'),
            'period_start' => optional($invoice->period_start)->format('Y-m-d'),
            'period_end' => optional($invoice->period_end)->format('Y-m-d'),
            'quantity' => (float) ($invoice->quantity ?: 1),
            'unit_price' => $invoice->unit_price !== null ? (float) $invoice->unit_price : (float) $invoice->amount,
            'vat_rate' => $invoice->vat_rate !== null ? (float) $invoice->vat_rate : null,
            'net_amount' => $net,
            'vat_amount' => $vat,
            'amount' => $gross,
            'amount_label' => number_format($gross, 2, '.', "'"),
            'currency' => $invoice->currency ?: 'CHF',
            'payment_status' => $invoice->payment_status,
            'billing_email' => $invoice->billing_email,
            'billing_name' => $invoice->billing_name,
            'billing_address_line' => $invoice->billing_address_line,
            'billing_postal_code' => $invoice->billing_postal_code,
            'billing_city' => $invoice->billing_city,
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
