<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function expertOffices(): HasMany
    {
        return $this->hasMany(ExpertOffice::class);
    }
}
