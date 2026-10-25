<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            // hero_match | national_top5 | canton_top3 | group_top | article_top | event_top
            $table->string('scope', 40)->index();
            $table->string('professional_group', 40)->nullable()->index();
            $table->foreignId('canton_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expert_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('position')->default(1);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('label')->nullable(); // e.g. "Q1 2026"
            $table->timestamps();

            $table->index(['scope', 'is_active', 'position']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_top_event')->default(false)->after('status');
            $table->string('price_label', 80)->nullable()->after('is_top_event');
            $table->string('capacity_label', 80)->nullable()->after('price_label');
            $table->string('registration_email')->nullable()->after('capacity_label');
        });

        Schema::table('experts', function (Blueprint $table) {
            $table->decimal('public_rating', 3, 1)->nullable()->after('logo_locked');
            $table->unsignedInteger('public_deals_count')->nullable()->after('public_rating');
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['public_rating', 'public_deals_count']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_top_event', 'price_label', 'capacity_label', 'registration_email']);
        });

        Schema::dropIfExists('rankings');
    }
};
