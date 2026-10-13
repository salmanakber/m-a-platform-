<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->string('professional_group', 40)->default('ma_specialist')->after('offers_sell');
            $table->string('logo_position', 20)->default('left')->after('logo_path');
            $table->boolean('logo_locked')->default(false)->after('logo_position');
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['professional_group', 'logo_position', 'logo_locked']);
        });
    }
};
