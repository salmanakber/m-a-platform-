<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionPrice extends Model
{
    protected $fillable = [
        'scope',
        'professional_group',
        'position',
        'price_chf',
        'note',
    ];

    protected $casts = [
        'position' => 'integer',
        'price_chf' => 'decimal:2',
    ];
}
