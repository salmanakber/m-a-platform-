<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $fillable = [
        'expert_id',
        'invoice_number',
        'invoice_date',
        'period_start',
        'period_end',
        'amount',
        'quantity',
        'unit_price',
        'vat_rate',
        'currency',
        'payment_status',
        'notes',
        'promotion_id',
        'invoice_product_id',
        'billing_email',
        'billing_name',
        'billing_address_line',
        'billing_postal_code',
        'billing_city',
        'service_title',
        'service_detail',
        'admin_notes',
        'confirmation_sent_at',
        'invoice_sent_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'confirmation_sent_at' => 'datetime',
        'invoice_sent_at' => 'datetime',
    ];

    public function netAmount(): float
    {
        $qty = (float) ($this->quantity ?: 1);
        $unit = $this->unit_price !== null ? (float) $this->unit_price : (float) $this->amount;

        return round($qty * $unit, 2);
    }

    public function vatAmount(): float
    {
        $rate = (float) ($this->vat_rate ?: 0);
        if ($rate <= 0) {
            return 0.0;
        }

        return round($this->netAmount() * ($rate / 100), 2);
    }

    public function grossAmount(): float
    {
        return round($this->netAmount() + $this->vatAmount(), 2);
    }

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InvoiceProduct::class, 'invoice_product_id');
    }

    public function linkedPromotion(): HasOne
    {
        return $this->hasOne(Promotion::class, 'invoice_id');
    }
}
