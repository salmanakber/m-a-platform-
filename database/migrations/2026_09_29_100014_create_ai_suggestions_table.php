<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->nullable()->constrained()->nullOnDelete();
            $table->string('suggestible_type')->nullable();
            $table->unsignedBigInteger('suggestible_id')->nullable();
            $table->string('field_name');
            $table->text('current_value')->nullable();
            $table->text('suggested_value');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('ai_run_id')->nullable();
            $table->timestamps();

            $table->index(['suggestible_type', 'suggestible_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
    }
};
