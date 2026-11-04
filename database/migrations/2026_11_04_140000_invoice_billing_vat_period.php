<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('invoice_date');
            $table->date('period_end')->nullable()->after('period_start');
            $table->decimal('quantity', 10, 2)->default(1)->after('amount');
            $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('unit_price');
            $table->string('billing_name')->nullable()->after('billing_email');
            $table->string('billing_address_line')->nullable()->after('billing_name');
            $table->string('billing_postal_code', 20)->nullable()->after('billing_address_line');
            $table->string('billing_city', 120)->nullable()->after('billing_postal_code');
        });

        Schema::table('experts', function (Blueprint $table) {
            $table->string('billing_email')->nullable()->after('email');
            $table->string('billing_name')->nullable()->after('billing_email');
            $table->string('billing_address_line')->nullable()->after('billing_name');
            $table->string('billing_postal_code', 20)->nullable()->after('billing_address_line');
            $table->string('billing_city', 120)->nullable()->after('billing_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'period_start',
                'period_end',
                'quantity',
                'unit_price',
                'vat_rate',
                'billing_name',
                'billing_address_line',
                'billing_postal_code',
                'billing_city',
            ]);
        });

        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn([
                'billing_email',
                'billing_name',
                'billing_address_line',
                'billing_postal_code',
                'billing_city',
            ]);
        });
    }
};
