<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->json('experience_areas')->nullable()->after('video_url');
            $table->json('diplomas')->nullable()->after('experience_areas');
            $table->json('interim_criteria')->nullable()->after('diplomas');
            $table->string('equity_capital', 120)->nullable()->after('interim_criteria');
            $table->string('cv_path')->nullable()->after('equity_capital');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('equity_capital', 120)->nullable()->after('buy_sell_context');
            $table->string('role_context', 40)->nullable()->after('equity_capital'); // buyer|seller|future_owner|other
        });

        Schema::create('invoice_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('CHF');
            $table->string('billing_period', 40)->nullable(); // monthly|one_time
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('invoice_product_id')->nullable()->after('promotion_id')->constrained('invoice_products')->nullOnDelete();
            $table->string('billing_email')->nullable()->after('notes');
            $table->string('service_title')->nullable()->after('billing_email');
            $table->text('service_detail')->nullable()->after('service_title');
            $table->text('admin_notes')->nullable()->after('service_detail');
            $table->timestamp('confirmation_sent_at')->nullable()->after('admin_notes');
            $table->timestamp('invoice_sent_at')->nullable()->after('confirmation_sent_at');
        });

        Schema::create('expert_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('template_key', 60)->default('classic');
            $table->json('payload')->nullable(); // editable CV fields
            $table->string('status', 30)->default('draft'); // draft|ready
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        // Promotion pricing matrix (scope + position → CHF/month)
        Schema::create('promotion_prices', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 40); // canton_top3 | national_top5 | article_top | group_top
            $table->string('professional_group', 40)->nullable();
            $table->unsignedTinyInteger('position');
            $table->decimal('price_chf', 10, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['scope', 'professional_group', 'position'], 'promo_price_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_prices');
        Schema::dropIfExists('expert_cvs');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_product_id');
            $table->dropColumn([
                'billing_email',
                'service_title',
                'service_detail',
                'admin_notes',
                'confirmation_sent_at',
                'invoice_sent_at',
            ]);
        });

        Schema::dropIfExists('invoice_products');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['equity_capital', 'role_context']);
        });

        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn([
                'experience_areas',
                'diplomas',
                'interim_criteria',
                'equity_capital',
                'cv_path',
            ]);
        });
    }
};
