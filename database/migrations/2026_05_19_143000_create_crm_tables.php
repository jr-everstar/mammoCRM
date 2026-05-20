<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_code')->unique();
            $table->string('plan_name');
            $table->decimal('selling_price', 12, 2);
            $table->unsignedInteger('report_commitment');
            $table->decimal('average_cost_per_report', 10, 2)->default(0);
            $table->unsignedInteger('included_ipad_quantity')->default(0);
            $table->unsignedInteger('included_sensor_set_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cost_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->decimal('value', 12, 2);
            $table->string('unit')->default('HKD');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('type')->default('amount');
            $table->decimal('value', 12, 4)->default(0);
            $table->foreignId('sales_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_tier_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tier');
            $table->decimal('threshold_amount', 12, 2);
            $table->decimal('tier_bonus', 12, 2);
            $table->decimal('cumulative_bonus', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('high_plan_accelerator_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('min_plan_c_or_above')->default(0);
            $table->unsignedInteger('min_plan_d_or_above')->default(0);
            $table->decimal('bonus', 12, 2);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('renewal_upgrade_rules', function (Blueprint $table) {
            $table->id();
            $table->string('deal_type')->unique();
            $table->string('name');
            $table->decimal('commission_rate', 8, 4);
            $table->decimal('monthly_tier_count_percentage', 8, 4)->default(0);
            $table->boolean('can_trigger_monthly_tier')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('definition')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_registration_number')->unique();
            $table->string('business_type')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_title')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('account_manager_id')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('prospect')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_name');
            $table->string('company_name')->nullable();
            $table->string('company_registration_number')->nullable()->index();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('source')->nullable();
            $table->string('business_type')->nullable();
            $table->foreignId('assigned_sales_id')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('New')->index();
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('converted_opportunity_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('opportunity_name');
            $table->foreignId('sales_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_deal_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->foreignId('assigned_sales_id')->constrained('users');
            $table->string('stage')->default('Lead-in')->index();
            $table->text('notes')->nullable();
            $table->text('lost_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunity_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('type')->default('note');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_user_id')->constrained('users');
            $table->foreignId('account_manager_id')->constrained('users');
            $table->foreignId('sales_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('deal_type')->default('new_deal')->index();
            $table->decimal('deal_amount', 12, 2);
            $table->string('payment_status')->default('Pending')->index();
            $table->date('payment_date')->nullable()->index();
            $table->date('contract_date')->nullable();
            $table->string('commission_status')->default('Pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('commission_runs', function (Blueprint $table) {
            $table->id();
            $table->date('month')->index();
            $table->foreignId('sales_user_id')->constrained('users');
            $table->string('status')->default('Pending')->index();
            $table->boolean('admin_monthly_tier_override')->default(false);
            $table->decimal('monthly_qualified_sales_amount', 12, 2)->default(0);
            $table->decimal('basic_commission', 12, 2)->default(0);
            $table->decimal('renewal_upgrade_commission', 12, 2)->default(0);
            $table->decimal('monthly_tier_bonus', 12, 2)->default(0);
            $table->decimal('high_plan_accelerator_bonus', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->decimal('pre_commission_gross_margin', 12, 2)->default(0);
            $table->decimal('post_commission_remaining_gross_margin', 12, 2)->default(0);
            $table->decimal('incentive_ratio', 10, 4)->default(0);
            $table->decimal('override_total_commission', 12, 2)->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['month', 'sales_user_id']);
        });

        Schema::create('commission_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->string('deal_type');
            $table->decimal('deal_amount', 12, 2);
            $table->decimal('basic_commission', 12, 2)->default(0);
            $table->decimal('renewal_upgrade_commission', 12, 2)->default(0);
            $table->decimal('pre_commission_gross_margin', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_run_items');
        Schema::dropIfExists('commission_runs');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('opportunity_activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('renewal_upgrade_rules');
        Schema::dropIfExists('high_plan_accelerator_rules');
        Schema::dropIfExists('monthly_tier_rules');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('cost_configs');
        Schema::dropIfExists('sales_plans');
    }
};
