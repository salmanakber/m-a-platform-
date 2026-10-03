<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canton_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position_number')->nullable();
            $table->unsignedInteger('queue_order')->default(0);
            $table->timestamps();

            $table->index(['canton_id', 'position_number', 'queue_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_waitlist');
    }
};
