<?php

namespace Database\Seeders;

use App\Models\PromotionPrice;
use App\Support\RankingScope;
use Illuminate\Database\Seeder;

class PromotionPriceSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Top-Positionen Experten pro Kanton
            [RankingScope::CANTON_TOP3, null, 1, 55, 'Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 2, 45, 'Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 3, 35, 'Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 4, 25, 'Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 5, 20, 'Experten Kanton'],
            // Top 5 Schweiz
            [RankingScope::NATIONAL_TOP5, null, 1, 80, 'Top 5 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 2, 55, 'Top 5 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 3, 45, 'Top 5 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 4, 35, 'Top 5 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 5, 25, 'Top 5 Schweiz'],
            // Top Fachbeiträge (Bereich / global)
            [RankingScope::ARTICLE_TOP, null, 1, 55, 'Top 3 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 2, 45, 'Top 3 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 3, 35, 'Top 3 Fachbeiträge'],
        ];

        foreach ($rows as [$scope, $group, $position, $price, $note]) {
            PromotionPrice::updateOrCreate(
                [
                    'scope' => $scope,
                    'professional_group' => $group,
                    'position' => $position,
                ],
                [
                    'price_chf' => $price,
                    'note' => $note,
                ]
            );
        }
    }
}
