<?php

namespace Database\Seeders;

use App\Models\InvoiceProduct;
use Illuminate\Database\Seeder;

class InvoiceProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['sku' => 'promo-canton-1', 'name' => 'Top-Position Kanton · Platz 1', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 10],
            ['sku' => 'promo-canton-2', 'name' => 'Top-Position Kanton · Platz 2', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 20],
            ['sku' => 'promo-canton-3', 'name' => 'Top-Position Kanton · Platz 3', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 30],
            ['sku' => 'promo-canton-4', 'name' => 'Top-Position Kanton · Platz 4', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 40],
            ['sku' => 'promo-canton-5', 'name' => 'Top-Position Kanton · Platz 5', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 50],
            ['sku' => 'promo-national-1', 'name' => 'Top 5 Schweiz · Platz 1', 'unit_price' => 80, 'billing_period' => 'monthly', 'sort_order' => 60],
            ['sku' => 'promo-national-2', 'name' => 'Top 5 Schweiz · Platz 2', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 70],
            ['sku' => 'promo-national-3', 'name' => 'Top 5 Schweiz · Platz 3', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 80],
            ['sku' => 'promo-national-4', 'name' => 'Top 5 Schweiz · Platz 4', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 90],
            ['sku' => 'promo-national-5', 'name' => 'Top 5 Schweiz · Platz 5', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 100],
            ['sku' => 'promo-article-1', 'name' => 'Top 3 Fachbeitrag · Platz 1', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 110],
            ['sku' => 'promo-article-2', 'name' => 'Top 3 Fachbeitrag · Platz 2', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 120],
            ['sku' => 'promo-article-3', 'name' => 'Top 3 Fachbeitrag · Platz 3', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 130],
            ['sku' => 'promo-article-home-1', 'name' => 'Top 3 Fachbeiträge Startseite · Platz 1', 'unit_price' => 80, 'billing_period' => 'monthly', 'sort_order' => 140],
            ['sku' => 'promo-article-home-2', 'name' => 'Top 3 Fachbeiträge Startseite · Platz 2', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 150],
            ['sku' => 'promo-article-home-3', 'name' => 'Top 3 Fachbeiträge Startseite · Platz 3', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 160],
            ['sku' => 'custom-service', 'name' => 'Individuelle Leistung', 'unit_price' => 0, 'billing_period' => 'one_time', 'sort_order' => 200, 'description' => 'Freier Betrag / Beschreibung durch Admin.'],
        ];

        foreach ($products as $product) {
            InvoiceProduct::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'description' => $product['description'] ?? null,
                    'unit_price' => $product['unit_price'],
                    'currency' => 'CHF',
                    'billing_period' => $product['billing_period'],
                    'is_active' => true,
                    'sort_order' => $product['sort_order'],
                ]
            );
        }
    }
}
