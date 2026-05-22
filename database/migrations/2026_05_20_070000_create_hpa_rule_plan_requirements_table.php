<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('high_plan_accelerator_rule_sales_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('high_plan_accelerator_rule_id')
                ->constrained(indexName: 'hpa_rule_plan_rule_fk')
                ->cascadeOnDelete();
            $table->foreignId('sales_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('required_quantity')->default(0);
            $table->timestamps();
            $table->unique(['high_plan_accelerator_rule_id', 'sales_plan_id'], 'hpa_rule_plan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('high_plan_accelerator_rule_sales_plan');
    }
};
