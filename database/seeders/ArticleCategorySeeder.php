<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Grundlagen und strategische Optionen',
            'Unternehmensbewertung',
            'Vorbereitung',
            'Psychologie und Kommunikation',
            'Recht, Steuern & Risiken',
            'Praxisberichte',
        ];

        foreach ($categories as $index => $name) {
            ArticleCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name_de' => $name,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
