<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertCv extends Model
{
    protected $fillable = [
        'expert_id',
        'created_by',
        'title',
        'template_key',
        'payload',
        'status',
        'pdf_path',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
