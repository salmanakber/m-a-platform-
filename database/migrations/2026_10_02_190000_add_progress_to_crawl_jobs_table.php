<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->string('progress_step', 40)->nullable()->after('notes');
            $table->string('progress_label', 255)->nullable()->after('progress_step');
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('progress_label');
        });
    }

    public function down(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->dropColumn(['progress_step', 'progress_label', 'progress_percent']);
        });
    }
};
