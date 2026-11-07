<?php

use App\Models\InvoiceProduct;
use App\Models\PromotionPrice;
use App\Support\RankingScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cleanup from a previous partial SQLite run.
        if (Schema::hasTable('promotions_old_tmp')) {
            if (Schema::hasTable('promotions') && DB::table('promotions')->count() === 0 && DB::table('promotions_old_tmp')->count() > 0) {
                Schema::drop('promotions');
                Schema::rename('promotions_old_tmp', 'promotions');
            } else {
                Schema::drop('promotions_old_tmp');
            }
        }

        Schema::table('promotions', function (Blueprint $table) {
            if (! Schema::hasColumn('promotions', 'scope')) {
                $table->string('scope', 40)->default(RankingScope::CANTON_TOP3)->after('expert_id');
            }
            if (! Schema::hasColumn('promotions', 'professional_group')) {
                $table->string('professional_group', 40)->nullable();
            }
            if (! Schema::hasColumn('promotions', 'article_category_id')) {
                $table->unsignedBigInteger('article_category_id')->nullable();
            }
        });

        try {
            Schema::table('promotions', function (Blueprint $table) {
                $table->foreign('article_category_id')
                    ->references('id')
                    ->on('article_categories')
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
        }

        $this->makeCantonIdNullable();

        try {
            Schema::table('promotions', function (Blueprint $table) {
                $table->index(['scope', 'position_number', 'status'], 'promotions_scope_pos_status_idx');
            });
        } catch (\Throwable) {
        }

        foreach ([
            [1, 80],
            [2, 55],
            [3, 45],
        ] as [$pos, $price]) {
            PromotionPrice::query()->updateOrCreate(
                [
                    'scope' => RankingScope::HERO_MATCH,
                    'professional_group' => null,
                    'position' => $pos,
                ],
                [
                    'price_chf' => $price,
                    'note' => 'Top empfohlen (Banner)',
                ]
            );
        }

        foreach ([
            ['video-pack-5', 'Video-Paket bis 5 Videos', '1. Video gratis inklusive. Monatspauschale für bis zu 5 Videos.', 30, 180],
            ['video-pack-10', 'Video-Paket bis 10 Videos', 'Monatspauschale für bis zu 10 Videos.', 50, 181],
            ['video-pack-over10', 'Video-Paket über 10 Videos', 'Monatspauschale für mehr als 10 Videos.', 150, 182],
        ] as [$sku, $name, $description, $price, $sort]) {
            InvoiceProduct::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'description' => $description,
                    'unit_price' => $price,
                    'currency' => 'CHF',
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'sort_order' => $sort,
                ]
            );
        }

        InvoiceProduct::query()->where('sku', 'video-extra')->update([
            'is_active' => false,
            'name' => 'Zusätzliches Experten-Video (ersetzt durch Pakete)',
        ]);
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            try {
                $table->dropIndex('promotions_scope_pos_status_idx');
            } catch (\Throwable) {
            }
            if (Schema::hasColumn('promotions', 'article_category_id')) {
                try {
                    $table->dropForeign(['article_category_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('article_category_id');
            }
            if (Schema::hasColumn('promotions', 'professional_group')) {
                $table->dropColumn('professional_group');
            }
            if (Schema::hasColumn('promotions', 'scope')) {
                $table->dropColumn('scope');
            }
        });

        InvoiceProduct::query()
            ->whereIn('sku', ['video-pack-5', 'video-pack-10', 'video-pack-over10'])
            ->delete();

        PromotionPrice::query()->where('scope', RankingScope::HERO_MATCH)->delete();
    }

    private function makeCantonIdNullable(): void
    {
        if (! $this->cantonIdIsNotNull()) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::rename('promotions', 'promotions_old_tmp');

            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
                $table->string('scope', 40)->default(RankingScope::CANTON_TOP3);
                $table->string('professional_group', 40)->nullable();
                $table->unsignedBigInteger('canton_id')->nullable();
                $table->unsignedBigInteger('article_category_id')->nullable();
                $table->unsignedTinyInteger('position_number');
                $table->string('status', 20)->default('pending');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();

                $table->foreign('canton_id')->references('id')->on('cantons')->nullOnDelete();
                $table->foreign('article_category_id')->references('id')->on('article_categories')->nullOnDelete();
            });

            foreach (DB::table('promotions_old_tmp')->get() as $row) {
                DB::table('promotions')->insert([
                    'id' => $row->id,
                    'expert_id' => $row->expert_id,
                    'scope' => $row->scope ?? RankingScope::CANTON_TOP3,
                    'professional_group' => $row->professional_group ?? null,
                    'canton_id' => $row->canton_id,
                    'article_category_id' => $row->article_category_id ?? null,
                    'position_number' => $row->position_number,
                    'status' => $row->status,
                    'starts_at' => $row->starts_at,
                    'ends_at' => $row->ends_at,
                    'invoice_id' => $row->invoice_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::drop('promotions_old_tmp');
            Schema::enableForeignKeyConstraints();

            return;
        }

        DB::statement('ALTER TABLE promotions MODIFY canton_id BIGINT UNSIGNED NULL');
    }

    private function cantonIdIsNotNull(): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA table_info(promotions)') as $col) {
                if ($col->name === 'canton_id') {
                    return (int) $col->notnull === 1;
                }
            }

            return false;
        }

        $row = DB::selectOne("SHOW COLUMNS FROM promotions WHERE Field = 'canton_id'");

        return $row && strtoupper((string) $row->Null) === 'NO';
    }
};
