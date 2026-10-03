<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->string('owner_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message');
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->foreignId('canton_id')->nullable()->constrained()->nullOnDelete();
            $table->string('buy_sell_context', 10)->nullable();
            $table->string('company_type')->nullable();
            $table->string('employee_count')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamp('customer_notified_at')->nullable();
            $table->timestamp('expert_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
