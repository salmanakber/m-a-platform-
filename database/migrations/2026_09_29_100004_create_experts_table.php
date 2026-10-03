<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->text('services_text')->nullable();
            $table->boolean('offers_buy')->default(false);
            $table->boolean('offers_sell')->default(false);
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_last_name')->nullable();
            $table->boolean('crawl_enabled')->default(false);
            $table->string('website_crawl_url')->nullable();
            $table->string('imported_from')->nullable();
            $table->string('import_fingerprint')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experts');
    }
};
