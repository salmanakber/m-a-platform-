<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canton extends Model
{
    protected $fillable = [
        'code',
        'name_de',
        'name_fr',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function expertOffices(): HasMany
    {
        return $this->hasMany(ExpertOffice::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function promotionPositions(): HasMany
    {
        return $this->hasMany(PromotionPosition::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function promotionWaitlistEntries(): HasMany
    {
        return $this->hasMany(PromotionWaitlist::class);
    }
}
