<?php

namespace Database\Seeders;

use App\Models\Canton;
use App\Models\PromotionPosition;
use Illuminate\Database\Seeder;

class PromotionPositionSeeder extends Seeder
{
    public function run(): void
    {
        Canton::query()->each(function (Canton $canton): void {
            for ($position = 1; $position <= 3; $position++) {
                PromotionPosition::updateOrCreate(
                    [
                        'canton_id' => $canton->id,
                        'position_number' => $position,
                    ],
                    []
                );
            }
        });
    }
}
