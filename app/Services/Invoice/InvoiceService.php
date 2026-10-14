<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\Promotion;
use App\Services\Settings\SettingsService;
use App\Support\InvoicePaymentStatus;
use App\Support\SettingKey;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function monthlyPromotionPrice(): float
    {
        return (float) $this->settings->get(SettingKey::PROMOTION_MONTHLY_PRICE_CHF, '0');
    }

    public function createForPromotion(Promotion $promotion, ?float $amount = null, string $currency = 'CHF'): Invoice
    {
        $promotion->loadMissing(['expert', 'invoice']);

        if ($promotion->invoice !== null) {
            return $promotion->invoice;
        }

        $amount = $amount ?? $this->monthlyPromotionPrice();

        return DB::transaction(function () use ($promotion, $amount, $currency) {
            $invoice = Invoice::query()->create([
                'expert_id' => $promotion->expert_id,
                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'amount' => $amount,
                'currency' => $currency,
                'payment_status' => InvoicePaymentStatus::PENDING,
                'notes' => sprintf(
                    'Promotion Kanton-ID %d · Position %d',
                    $promotion->canton_id,
                    $promotion->position_number
                ),
                'promotion_id' => $promotion->id,
            ]);

            $promotion->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });
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
}
