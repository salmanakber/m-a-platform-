<?php

use App\Models\Canton;
use App\Models\InvoiceProduct;
use App\Models\PromotionPosition;
use App\Models\PromotionPrice;
use App\Support\RankingScope;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Canton promotion slots: add Platz 6
        Canton::query()->each(function (Canton $canton): void {
            PromotionPosition::query()->firstOrCreate([
                'canton_id' => $canton->id,
                'position_number' => 6,
            ]);
        });

        $priceRows = [
            [RankingScope::CANTON_TOP3, null, 1, 55, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 2, 45, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 3, 35, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 4, 25, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 5, 20, 'Top 6 Experten Kanton'],
            [RankingScope::CANTON_TOP3, null, 6, 20, 'Top 6 Experten Kanton'],
            [RankingScope::NATIONAL_TOP5, null, 1, 80, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 2, 55, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 3, 45, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 4, 35, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 5, 25, 'Top 6 Schweiz'],
            [RankingScope::NATIONAL_TOP5, null, 6, 20, 'Top 6 Schweiz'],
            [RankingScope::GROUP_TOP, null, 1, 80, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 2, 55, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 3, 45, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 4, 35, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 5, 25, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::GROUP_TOP, null, 6, 20, 'Top 6 Fachgruppe Schweiz'],
            [RankingScope::ARTICLE_TOP, null, 1, 55, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 2, 45, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 3, 35, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 4, 25, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 5, 20, 'Top 6 Fachbeiträge'],
            [RankingScope::ARTICLE_TOP, null, 6, 20, 'Top 6 Fachbeiträge'],
        ];

        foreach ($priceRows as [$scope, $group, $position, $price, $note]) {
            PromotionPrice::query()->updateOrCreate(
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

        $products = [
            ['sku' => 'promo-canton-6', 'name' => 'Top 6 Kanton · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 55],
            ['sku' => 'promo-national-6', 'name' => 'Top 6 Schweiz · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 105],
            ['sku' => 'promo-article-4', 'name' => 'Top 6 Fachbeitrag · Platz 4', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 135],
            ['sku' => 'promo-article-5', 'name' => 'Top 6 Fachbeitrag · Platz 5', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 136],
            ['sku' => 'promo-article-6', 'name' => 'Top 6 Fachbeitrag · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 137],
            ['sku' => 'video-top-6', 'name' => 'Top 6 Video Startseite · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 176],
        ];

        foreach ($products as $product) {
            InvoiceProduct::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'unit_price' => $product['unit_price'],
                    'currency' => 'CHF',
                    'billing_period' => $product['billing_period'],
                    'is_active' => true,
                    'sort_order' => $product['sort_order'],
                ]
            );
        }

        // Rename Top 5 → Top 6 on existing products; deactivate replaced homepage Top 3 Fachbeiträge
        $renames = [
            'promo-canton-1' => 'Top 6 Kanton · Platz 1',
            'promo-canton-2' => 'Top 6 Kanton · Platz 2',
            'promo-canton-3' => 'Top 6 Kanton · Platz 3',
            'promo-canton-4' => 'Top 6 Kanton · Platz 4',
            'promo-canton-5' => 'Top 6 Kanton · Platz 5',
            'promo-national-1' => 'Top 6 Schweiz · Platz 1',
            'promo-national-2' => 'Top 6 Schweiz · Platz 2',
            'promo-national-3' => 'Top 6 Schweiz · Platz 3',
            'promo-national-4' => 'Top 6 Schweiz · Platz 4',
            'promo-national-5' => 'Top 6 Schweiz · Platz 5',
            'promo-article-1' => 'Top 6 Fachbeitrag · Platz 1',
            'promo-article-2' => 'Top 6 Fachbeitrag · Platz 2',
            'promo-article-3' => 'Top 6 Fachbeitrag · Platz 3',
            'video-top-1' => 'Top 6 Video Startseite · Platz 1',
            'video-top-2' => 'Top 6 Video Startseite · Platz 2',
            'video-top-3' => 'Top 6 Video Startseite · Platz 3',
            'video-top-4' => 'Top 6 Video Startseite · Platz 4',
            'video-top-5' => 'Top 6 Video Startseite · Platz 5',
        ];

        foreach ($renames as $sku => $name) {
            InvoiceProduct::query()->where('sku', $sku)->update(['name' => $name]);
        }

        InvoiceProduct::query()
            ->whereIn('sku', ['promo-article-home-1', 'promo-article-home-2', 'promo-article-home-3'])
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        PromotionPosition::query()->where('position_number', 6)->delete();

        PromotionPrice::query()
            ->where('position', 6)
            ->orWhere('scope', RankingScope::GROUP_TOP)
            ->delete();

        InvoiceProduct::query()
            ->whereIn('sku', [
                'promo-canton-6',
                'promo-national-6',
                'promo-article-4',
                'promo-article-5',
                'promo-article-6',
                'video-top-6',
            ])
            ->delete();
    }
};
