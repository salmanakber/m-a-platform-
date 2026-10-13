<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('event_type', 40)->default('other'); // workshop, presentation, hearing, other
            $table->date('starts_on')->nullable();
            $table->time('starts_at_time')->nullable();
            $table->date('ends_on')->nullable();
            $table->time('ends_at_time')->nullable();
            $table->string('location_name')->nullable();
            $table->string('address_line')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->foreignId('canton_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source_url')->nullable()->index();
            $table->string('source_attribution')->nullable();
            $table->string('content_fingerprint')->nullable()->index();
            $table->boolean('is_crawled')->default(false);
            $table->boolean('needs_review')->default(false);
            $table->string('status', 20)->default('draft'); // draft, published, blocked
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
