<?php

namespace Database\Seeders;

use App\Models\PromotionPrice;
use App\Support\RankingScope;
use Illuminate\Database\Seeder;

class PromotionPriceSeeder extends Seeder
{
    public function run(): void
    {
        // Matrix: national / Fachgruppe Startseite = 80/55/45/35/25/20
        // Kanton / Fachbeiträge pro Bereich = 55/45/35/25/20/20
        $rows = [
            // Top 6 Experten pro Kanton
            [RankingScope::CANTON_TOP3, null, 1, 55, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 2, 45, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 3, 35, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 4, 25, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 5, 20, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 6, 20, 'Top 6 Experten Kanton'],
            // Top 6 Schweiz (Berater Startseite)
            [RankingScope::NATIONAL_TOP5, null, 1, 80, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 2, 55, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 3, 45, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 4, 35, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 5, 25, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 6, 20, 'Top 6 Schweiz'],
            // Top 6 Fachgruppe (Juristen, Treuhand, Ad Interim, Marktplätze)
            [RankingScope::GROUP_TOP, null, 1, 80, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 2, 55, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 3, 45, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 4, 35, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 5, 25, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 6, 20, 'Top 6 Fachgruppe Schweiz'],
            // Top 6 Fachbeiträge pro Bereich
            [RankingScope::ARTICLE_TOP, null, 1, 55, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 2, 45, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 3, 35, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 4, 25, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 5, 20, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 6, 20, 'Top 6 Fachbeiträge'],
            // Top empfohlen (Banner)
            [RankingScope::HERO_MATCH, null, 1, 80, 'Top empfohlen'],
            [RankingScope::HERO_MATCH, null, 2, 55, 'Top empfohlen'],
            [RankingScope::HERO_MATCH, null, 3, 45, 'Top empfohlen'],
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
