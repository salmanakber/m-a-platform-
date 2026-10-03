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
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function linkedPromotion(): HasOne
    {
        return $this->hasOne(Promotion::class, 'invoice_id');
    }
}
