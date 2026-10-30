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
            [
                'name_de' => 'Nachfolge-Phasen & Strategie',
                'description_de' => 'Diese Struktur hilft Nutzern, genau in der Phase einzusteigen, in der sie sich gerade befinden: Vorbereitung & Planung, Unternehmensbewertung & Finanzen, Transaktionsphase (Kauf & Verkauf) sowie Integration & Übergabe.',
            ],
            [
                'name_de' => 'Nachfolgemodelle',
                'description_de' => 'Zielgruppen-Fokus für die Form der Übergabe: familieninterne Nachfolge (Family Buy-Out), Management Buy-Out / Buy-In (MBO/MBI) sowie Verkauf an Dritte (M&A).',
            ],
            [
                'name_de' => 'Fachwissen & Experten-Disziplinen',
                'description_de' => 'Komplexe, harte Fakten für jede Nachfolge: Recht & Verträge, Steuern & Altersvorsorge sowie Finanzierung.',
            ],
            [
                'name_de' => 'Diverse weitere Themen',
                'description_de' => 'Weitere Fachbeiträge rund um Unternehmensnachfolge, die sich nicht einer der drei Kernstrukturen zuordnen.',
            ],
        ];

        $keepSlugs = [];
        foreach ($categories as $index => $category) {
            $slug = Str::slug($category['name_de']);
            $keepSlugs[] = $slug;
            ArticleCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_de' => $category['name_de'],
                    'description_de' => $category['description_de'],
                    'sort_order' => $index + 1,
                ]
            );
        }

        $legacyMap = [
            'grundlagen-und-strategische-optionen' => 'nachfolge-phasen-strategie',
            'vorbereitung' => 'nachfolge-phasen-strategie',
            'unternehmensbewertung' => 'nachfolge-phasen-strategie',
            'psychologie-und-kommunikation' => 'nachfolgemodelle',
            'recht-steuern-risiken' => 'fachwissen-experten-disziplinen',
            'praxisberichte' => 'diverse-weitere-themen',
        ];

        foreach ($legacyMap as $fromSlug => $toSlug) {
            $fromId = ArticleCategory::query()->where('slug', $fromSlug)->value('id');
            $toId = ArticleCategory::query()->where('slug', $toSlug)->value('id');
            if ($fromId && $toId) {
                ArticleCategory::query()->find($fromId)?->articles()->update(['category_id' => $toId]);
            }
        }

        ArticleCategory::query()->whereNotIn('slug', $keepSlugs)->delete();
    }
}
