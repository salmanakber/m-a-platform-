<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->string('contact_photo_path')->nullable()->after('logo_locked');
            $table->string('video_url', 500)->nullable()->after('contact_photo_path');
        });

        Schema::table('article_categories', function (Blueprint $table) {
            $table->text('description_de')->nullable()->after('name_de');
        });

        Schema::table('rankings', function (Blueprint $table) {
            $table->foreignId('article_category_id')->nullable()->after('canton_id')->constrained('article_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rankings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_category_id');
        });

        Schema::table('article_categories', function (Blueprint $table) {
            $table->dropColumn('description_de');
        });

        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['contact_photo_path', 'video_url']);
        });
    }
};
