<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('url', 500);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_complimentary')->default(false); // first free slot
            $table->string('status', 30)->default('active'); // active|pending_payment|hidden
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();

            $table->index(['expert_id', 'status']);
            $table->index(['status', 'sort_order']);
        });

        // Seed video invoice products if table exists
        if (Schema::hasTable('invoice_products')) {
            $now = now();
            $rows = [
                [
                    'sku' => 'video-extra',
                    'name' => 'Zusätzliches Experten-Video',
                    'description' => 'Weitere Videos nach dem ersten kostenlosen Upload. Freischaltung nach Zahlung.',
                    'unit_price' => 45,
                    'currency' => 'CHF',
                    'billing_period' => 'one_time',
                    'is_active' => true,
                    'sort_order' => 170,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'video-top-1',
                    'name' => 'Top 5 Video Startseite · Platz 1',
                    'description' => 'Hervorgehobenes Video auf der Startseite (Fachbeiträge-Bereich).',
                    'unit_price' => 80,
                    'currency' => 'CHF',
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'sort_order' => 171,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'video-top-2',
                    'name' => 'Top 5 Video Startseite · Platz 2',
                    'description' => null,
                    'unit_price' => 55,
                    'currency' => 'CHF',
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'sort_order' => 172,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'video-top-3',
                    'name' => 'Top 5 Video Startseite · Platz 3',
                    'description' => null,
                    'unit_price' => 45,
                    'currency' => 'CHF',
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'sort_order' => 173,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'video-top-4',
                    'name' => 'Top 5 Video Startseite · Platz 4',
                    'description' => null,
                    'unit_price' => 35,
                    'currency' => 'CHF',
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'sort_order' => 174,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'sku' => 'video-top-5',
                    'name' => 'Top 5 Video Startseite · Platz 5',
                    'description' => null,
                    'unit_price' => 25,
                    'currency' => 'CHF',
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'sort_order' => 175,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($rows as $row) {
                $exists = DB::table('invoice_products')->where('sku', $row['sku'])->exists();
                if (! $exists) {
                    DB::table('invoice_products')->insert($row);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_videos');
    }
};
