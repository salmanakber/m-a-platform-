<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->boolean('crawl_import_articles')->default(true)->after('crawl_enabled');
            $table->boolean('crawl_import_events')->default(true)->after('crawl_import_articles');
            $table->boolean('crawl_use_ai')->default(true)->after('crawl_import_events');
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['crawl_import_articles', 'crawl_import_events', 'crawl_use_ai']);
        });
    }
};
