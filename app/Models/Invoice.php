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
        'amount',
        'currency',
        'payment_status',
        'notes',
        'promotion_id',
        'invoice_product_id',
        'billing_email',
        'service_title',
        'service_detail',
        'admin_notes',
        'confirmation_sent_at',
        'invoice_sent_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
        'confirmation_sent_at' => 'datetime',
        'invoice_sent_at' => 'datetime',
    ];

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
