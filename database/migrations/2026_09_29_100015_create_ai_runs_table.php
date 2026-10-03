<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('purpose')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('prompt_excerpt')->nullable();
            $table->longText('response')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::table('ai_suggestions', function (Blueprint $table) {
            $table->foreign('ai_run_id')->references('id')->on('ai_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_suggestions', function (Blueprint $table) {
            $table->dropForeign(['ai_run_id']);
        });

        Schema::dropIfExists('ai_runs');
    }
};
