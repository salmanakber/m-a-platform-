<?php

namespace Database\Seeders;

use App\Models\InvoiceProduct;
use Illuminate\Database\Seeder;

class InvoiceProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Kanton: 55/45/35/25/20/20
            ['sku' => 'promo-canton-1', 'name' => 'Top 6 Kanton · Platz 1', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 10],
            ['sku' => 'promo-canton-2', 'name' => 'Top 6 Kanton · Platz 2', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 20],
            ['sku' => 'promo-canton-3', 'name' => 'Top 6 Kanton · Platz 3', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 30],
            ['sku' => 'promo-canton-4', 'name' => 'Top 6 Kanton · Platz 4', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 40],
            ['sku' => 'promo-canton-5', 'name' => 'Top 6 Kanton · Platz 5', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 50],
            ['sku' => 'promo-canton-6', 'name' => 'Top 6 Kanton · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 55],
            // Schweiz / Startseite: 80/55/45/35/25/20
            ['sku' => 'promo-national-1', 'name' => 'Top 6 Schweiz · Platz 1', 'unit_price' => 80, 'billing_period' => 'monthly', 'sort_order' => 60],
            ['sku' => 'promo-national-2', 'name' => 'Top 6 Schweiz · Platz 2', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 70],
            ['sku' => 'promo-national-3', 'name' => 'Top 6 Schweiz · Platz 3', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 80],
            ['sku' => 'promo-national-4', 'name' => 'Top 6 Schweiz · Platz 4', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 90],
            ['sku' => 'promo-national-5', 'name' => 'Top 6 Schweiz · Platz 5', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 100],
            ['sku' => 'promo-national-6', 'name' => 'Top 6 Schweiz · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 105],
            // Fachbeiträge pro Bereich: 55/45/35/25/20/20
            ['sku' => 'promo-article-1', 'name' => 'Top 6 Fachbeitrag · Platz 1', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 110],
            ['sku' => 'promo-article-2', 'name' => 'Top 6 Fachbeitrag · Platz 2', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 120],
            ['sku' => 'promo-article-3', 'name' => 'Top 6 Fachbeitrag · Platz 3', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 130],
            ['sku' => 'promo-article-4', 'name' => 'Top 6 Fachbeitrag · Platz 4', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 135],
            ['sku' => 'promo-article-5', 'name' => 'Top 6 Fachbeitrag · Platz 5', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 136],
            ['sku' => 'promo-article-6', 'name' => 'Top 6 Fachbeitrag · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 137],
            // Top 3 Fachbeiträge Startseite — ersetzt (inaktiv)
            ['sku' => 'promo-article-home-1', 'name' => 'Top 3 Fachbeiträge Startseite · Platz 1 (ersetzt)', 'unit_price' => 80, 'billing_period' => 'monthly', 'sort_order' => 140, 'is_active' => false],
            ['sku' => 'promo-article-home-2', 'name' => 'Top 3 Fachbeiträge Startseite · Platz 2 (ersetzt)', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 150, 'is_active' => false],
            ['sku' => 'promo-article-home-3', 'name' => 'Top 3 Fachbeiträge Startseite · Platz 3 (ersetzt)', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 160, 'is_active' => false],
            // Videos Startseite: 80/55/45/35/25/20
            ['sku' => 'video-top-1', 'name' => 'Top 6 Video Startseite · Platz 1', 'unit_price' => 80, 'billing_period' => 'monthly', 'sort_order' => 171],
            ['sku' => 'video-top-2', 'name' => 'Top 6 Video Startseite · Platz 2', 'unit_price' => 55, 'billing_period' => 'monthly', 'sort_order' => 172],
            ['sku' => 'video-top-3', 'name' => 'Top 6 Video Startseite · Platz 3', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 173],
            ['sku' => 'video-top-4', 'name' => 'Top 6 Video Startseite · Platz 4', 'unit_price' => 35, 'billing_period' => 'monthly', 'sort_order' => 174],
            ['sku' => 'video-top-5', 'name' => 'Top 6 Video Startseite · Platz 5', 'unit_price' => 25, 'billing_period' => 'monthly', 'sort_order' => 175],
            ['sku' => 'video-top-6', 'name' => 'Top 6 Video Startseite · Platz 6', 'unit_price' => 20, 'billing_period' => 'monthly', 'sort_order' => 176],
            ['sku' => 'video-pack-5', 'name' => 'Video-Paket bis 5 Videos', 'unit_price' => 30, 'billing_period' => 'monthly', 'sort_order' => 180, 'description' => '1. Video gratis inklusive. Monatspauschale für bis zu 5 Videos.'],
            ['sku' => 'video-pack-10', 'name' => 'Video-Paket bis 10 Videos', 'unit_price' => 50, 'billing_period' => 'monthly', 'sort_order' => 181, 'description' => 'Monatspauschale für bis zu 10 Videos.'],
            ['sku' => 'video-pack-over10', 'name' => 'Video-Paket über 10 Videos', 'unit_price' => 150, 'billing_period' => 'monthly', 'sort_order' => 182, 'description' => 'Monatspauschale für mehr als 10 Videos.'],
            ['sku' => 'video-extra', 'name' => 'Zusätzliches Experten-Video (ersetzt durch Pakete)', 'unit_price' => 45, 'billing_period' => 'monthly', 'sort_order' => 179, 'is_active' => false],
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
                    'is_active' => $product['is_active'] ?? true,
                    'sort_order' => $product['sort_order'],
                ]
            );
        }
    }
}
