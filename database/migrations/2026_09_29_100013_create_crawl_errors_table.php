<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_job_id')->constrained()->cascadeOnDelete();
            $table->string('url')->nullable();
            $table->string('error_type')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_errors');
    }
};
