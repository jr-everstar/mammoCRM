<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_plans', function (Blueprint $table) {
            $table->decimal('monthly_tier_count_percentage', 8, 4)->default(100)->after('display_order');
            $table->boolean('can_trigger_monthly_tier')->default(false)->after('monthly_tier_count_percentage');
            $table->boolean('hpa_eligible')->default(false)->after('can_trigger_monthly_tier');
            $table->string('hpa_level')->default('none')->after('hpa_eligible');
        });
    }

    public function down(): void
    {
        Schema::table('sales_plans', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_tier_count_percentage',
                'can_trigger_monthly_tier',
                'hpa_eligible',
                'hpa_level',
            ]);
        });
    }
};
