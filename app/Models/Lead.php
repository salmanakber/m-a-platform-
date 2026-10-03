<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'expert_id',
        'owner_name',
        'email',
        'phone',
        'message',
        'company_name',
        'industry',
        'canton_id',
        'buy_sell_context',
        'company_type',
        'employee_count',
        'status',
        'customer_notified_at',
        'expert_notified_at',
    ];

    protected $casts = [
        'customer_notified_at' => 'datetime',
        'expert_notified_at' => 'datetime',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class);
    }
}
