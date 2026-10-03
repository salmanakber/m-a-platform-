<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canton_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position_number');
            $table->timestamps();

            $table->unique(['canton_id', 'position_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_positions');
    }
};
