<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_plan_accelerator_rules', function (Blueprint $table) {
            $table->unsignedInteger('min_hpa_eligible_deals')->default(0)->after('name');
        });

        DB::table('high_plan_accelerator_rules')
            ->select('id', 'min_plan_c_or_above', 'min_plan_d_or_above')
            ->orderBy('id')
            ->get()
            ->each(function (object $rule): void {
                DB::table('high_plan_accelerator_rules')
                    ->where('id', $rule->id)
                    ->update([
                        'min_hpa_eligible_deals' => max((int) $rule->min_plan_c_or_above, (int) $rule->min_plan_d_or_above),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('high_plan_accelerator_rules', function (Blueprint $table) {
            $table->dropColumn('min_hpa_eligible_deals');
        });
    }
};
