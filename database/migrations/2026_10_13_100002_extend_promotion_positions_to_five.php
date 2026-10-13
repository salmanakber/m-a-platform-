<?php

use App\Models\Canton;
use App\Models\PromotionPosition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Canton::query()->each(function (Canton $canton): void {
            foreach ([4, 5] as $position) {
                PromotionPosition::query()->firstOrCreate([
                    'canton_id' => $canton->id,
                    'position_number' => $position,
                ]);
            }
        });
    }

    public function down(): void
    {
        PromotionPosition::query()
            ->whereIn('position_number', [4, 5])
            ->delete();
    }
};
