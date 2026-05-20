<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountDocument;
use App\Models\Asset;
use App\Models\CommissionRule;
use App\Models\Contact;
use App\Models\CostConfig;
use App\Models\Deal;
use App\Models\HighPlanAcceleratorRule;
use App\Models\Lead;
use App\Models\MonthlyTierRule;
use App\Models\Opportunity;
use App\Models\OpportunityStageRule;
use App\Models\RenewalUpgradeRule;
use App\Models\SalesPlan;
use App\Models\TrialSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $salesRole = Role::firstOrCreate(['name' => 'sales']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'status' => 'active', 'must_use_microsoft_login' => false]
        );
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->syncRoles([$adminRole, $salesRole]);

        $sales = User::updateOrCreate(
            ['email' => 'sales@example.com'],
            ['name' => 'Sales Demo', 'password' => Hash::make('password'), 'status' => 'active', 'must_use_microsoft_login' => false]
        );
        $sales->forceFill(['email_verified_at' => now()])->save();
        $sales->syncRoles([$salesRole]);

        $staff = User::updateOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Staff Demo', 'password' => Hash::make('password'), 'status' => 'active', 'must_use_microsoft_login' => false]
        );
        $staff->forceFill(['email_verified_at' => now()])->save();
        $staff->syncRoles([$staffRole]);

        $plans = [
            ['PLAN-0001', 'PLAN β', 16000, 60, 267, 1, 2, 100, false, false, 'none'],
            ['PLAN-0002', 'PLAN A', 32000, 120, 267, 1, 2, 100, false, false, 'none'],
            ['PLAN-0003', 'PLAN B', 61000, 240, 256, 1, 2, 100, true, false, 'none'],
            ['PLAN-0004', 'PLAN C', 144000, 600, 240, 2, 4, 100, true, true, 'plan_c_or_above'],
            ['PLAN-0005', 'PLAN D', 378000, 1680, 225, 5, 10, 100, true, true, 'plan_d_or_above'],
        ];

        foreach ($plans as $index => [$code, $name, $price, $reports, $avgCost, $ipads, $sensors, $tierCount, $tierTrigger, $hpaEligible, $hpaLevel]) {
            SalesPlan::updateOrCreate(
                ['plan_name' => $name],
                [
                    'plan_code' => $code,
                    'plan_name' => $name,
                    'selling_price' => $price,
                    'report_commitment' => $reports,
                    'average_cost_per_report' => $avgCost,
                    'included_ipad_quantity' => $ipads,
                    'included_sensor_set_quantity' => $sensors,
                    'is_active' => true,
                    'display_order' => $index + 1,
                    'monthly_tier_count_percentage' => $tierCount,
                    'can_trigger_monthly_tier' => $tierTrigger,
                    'hpa_eligible' => $hpaEligible,
                    'hpa_level' => $hpaLevel,
                ]
            );
        }

        foreach ([
            ['report_cost_per_report', 'Report cost per report', 100, 'HKD/report', 'Variable report processing cost.'],
            ['managed_ipad_cost', 'Managed iPad cost', 5000, 'HKD/unit', 'Managed iPad hardware cost.'],
            ['sensor_set_cost', 'Sensor set cost', 500, 'HKD/set', 'Sensor set hardware cost.'],
        ] as [$key, $name, $value, $unit, $description]) {
            CostConfig::updateOrCreate(compact('key'), compact('name', 'value', 'unit', 'description') + ['is_active' => true]);
        }

        foreach ([
            ['everstar_address', 'EverStar Registered Address', 'Hong Kong', 'Used in the trial agreement party block.'],
            ['default_return_address', 'Default Return Address', 'EverStar Hong Kong Office', 'Default return address for trial materials.'],
            ['default_trial_fee', 'Default Trial Fee', 'Waived', 'Shown as the trial fee unless overwritten during generation.'],
            ['default_security_deposit', 'Default Security Deposit', 'N/A', 'Shown as the security deposit unless overwritten during generation.'],
            ['director_name', 'Director Signatory Name', 'Director', 'Default director-level signer for the EverStar signature block.'],
        ] as [$key, $name, $value, $description]) {
            TrialSetting::updateOrCreate(compact('key'), compact('name', 'value', 'description'));
        }

        foreach ([
            ['IPAD-001', 'ipad', null, 'IPAD-SN-001', 'Managed iPad', 'available', 'good', 'Central Office'],
            ['IPAD-002', 'ipad', null, 'IPAD-SN-002', 'Managed iPad', 'available', 'good', 'Central Office'],
            ['SEN-L-001', 'sensor', 'left', 'LS-001', 'mammo care Left Sensor', 'available', 'good', 'Central Office'],
            ['SEN-R-001', 'sensor', 'right', 'RS-001', 'mammo care Right Sensor', 'available', 'good', 'Central Office'],
            ['SEN-L-002', 'sensor', 'left', 'LS-002', 'mammo care Left Sensor', 'available', 'good', 'Central Office'],
            ['SEN-R-002', 'sensor', 'right', 'RS-002', 'mammo care Right Sensor', 'available', 'good', 'Central Office'],
            ['CHG-001', 'charger', null, 'CHG-001', 'iPad charger and cable', 'available', 'good', 'Central Office'],
            ['CASE-001', 'case', null, 'CASE-001', 'iPad protective case', 'available', 'good', 'Central Office'],
        ] as [$assetTag, $type, $side, $serialNumber, $modelName, $status, $condition, $location]) {
            Asset::updateOrCreate(
                ['asset_tag' => $assetTag],
                [
                    'type' => $type,
                    'side' => $side,
                    'serial_number' => $serialNumber,
                    'model_name' => $modelName,
                    'status' => $status,
                    'condition' => $condition,
                    'location' => $location,
                ]
            );
        }

        foreach ([
            ['lead_in_reward', 'Lead-in Reward', 'amount', 100, null],
            ['trial_reward', 'Trial Reward', 'amount', 200, null],
            ['initial_payout_percentage', 'Lead / Trial Initial Payout', 'percentage', 40, null],
            ['completion_payout_percentage', 'Lead / Trial Completion Payout', 'percentage', 60, null],
        ] as [$key, $name, $type, $value, $planId]) {
            CommissionRule::updateOrCreate(['key' => $key], compact('name', 'type', 'value') + ['sales_plan_id' => $planId, 'is_active' => true]);
        }

        $dealCommissions = ['PLAN β' => 500, 'PLAN A' => 1300, 'PLAN B' => 3700, 'PLAN C' => 11200, 'PLAN D' => 36500];
        foreach ($dealCommissions as $planName => $amount) {
            $plan = SalesPlan::where('plan_name', $planName)->first();
            CommissionRule::updateOrCreate(
                ['key' => 'new_deal_commission_'.$plan->plan_code],
                ['name' => $plan->plan_name.' Done Deal Commission', 'type' => 'amount', 'value' => $amount, 'sales_plan_id' => $plan->id, 'is_active' => true]
            );
        }

        foreach ([[1, 100000, 1500, 1500], [2, 150000, 2500, 4000], [3, 300000, 5000, 9000], [4, 500000, 8000, 17000], [5, 800000, 12000, 29000]] as [$tier, $threshold, $bonus, $cumulative]) {
            MonthlyTierRule::updateOrCreate(['tier' => $tier], ['threshold_amount' => $threshold, 'tier_bonus' => $bonus, 'cumulative_bonus' => $cumulative, 'is_active' => true]);
        }

        $planC = SalesPlan::where('plan_name', 'PLAN C')->first();
        $planD = SalesPlan::where('plan_name', 'PLAN D')->first();

        foreach ([
            ['HPA1', '2 x PLAN C', [$planC->id => 2], 5000, 1],
            ['HPA2', '1 x PLAN C + 1 x PLAN D', [$planC->id => 1, $planD->id => 1], 8000, 2],
            ['HPA3', '2 x PLAN D', [$planD->id => 2], 12000, 3],
        ] as [$code, $name, $planRequirements, $bonus, $priority]) {
            $rule = HighPlanAcceleratorRule::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'min_hpa_eligible_deals' => array_sum($planRequirements),
                    'min_plan_c_or_above' => $planRequirements[$planC->id] ?? 0,
                    'min_plan_d_or_above' => $planRequirements[$planD->id] ?? 0,
                    'bonus' => $bonus,
                    'priority' => $priority,
                    'is_active' => true,
                ]
            );
            $rule->salesPlanRequirements()->sync(collect($planRequirements)->mapWithKeys(fn ($quantity, $planId) => [
                $planId => ['required_quantity' => $quantity],
            ])->all());
        }

        foreach ([
            ['passive_renewal_upgrade', 'Passive Renewal / Upgrade', 1.5, 0, false],
            ['am_managed_renewal', 'AM Managed Renewal', 3.5, 25, false],
            ['am_managed_upgrade', 'AM Managed Upgrade', 5, 50, false],
        ] as [$dealType, $name, $rate, $countIn, $canTrigger]) {
            RenewalUpgradeRule::updateOrCreate(['deal_type' => $dealType], ['name' => $name, 'commission_rate' => $rate, 'monthly_tier_count_percentage' => $countIn, 'can_trigger_monthly_tier' => $canTrigger, 'is_active' => true]);
        }

        foreach ([
            ['Lead-in', '初步接洽', 10, '確認需求、預算、決策人及下一步。'],
            ['Meeting / Demo', '會議 / 示範', 25, '已安排或完成需求會議 / Demo。'],
            ['Trial', '試用', 40, '客戶正在試用或等待試用結果。'],
            ['Proposal', '報價', 60, '已提供方案及報價。'],
            ['Negotiation', '洽談', 80, '正在處理條款、付款或簽約安排。'],
            ['Done Deal', '已成交', 100, '已簽約或成交，應建立 Deal。'],
            ['Lost', '已流失', 0, '不再跟進，必須記錄流失原因。'],
        ] as $index => [$stage, $label, $probability, $guidance]) {
            OpportunityStageRule::updateOrCreate(
                ['stage' => $stage],
                compact('label', 'probability', 'guidance') + ['display_order' => $index + 1, 'is_active' => true]
            );
        }

        $account = Account::updateOrCreate([
            'company_registration_number' => 'HK-MAMMO-001',
        ], [
            'company_name' => 'Harbour Wellness Clinic',
            'business_type' => 'Clinic',
            'contact_person_name' => 'Winnie Chan',
            'contact_person_title' => 'Director',
            'contact_phone' => '+852 3000 1000',
            'contact_email' => 'winnie@example.com',
            'address' => 'Central, Hong Kong',
            'account_manager_id' => $sales->id,
            'created_by' => $sales->id,
            'status' => 'active',
            'notes' => 'Seed account for demo commission runs.',
        ]);

        Contact::updateOrCreate([
            'account_id' => $account->id,
            'email' => 'winnie@example.com',
        ], [
            'name' => 'Winnie Chan',
            'title' => 'Director',
            'phone' => '+852 3000 1000',
            'whatsapp' => '+852 3000 1000',
            'is_primary' => true,
            'status' => 'active',
            'notes' => 'Primary business contact.',
        ]);

        Lead::updateOrCreate(['lead_name' => 'Tsim Sha Tsui Beauty Lead'], [
            'company_name' => 'TST Beauty Centre',
            'company_registration_number' => 'HK-MAMMO-002',
            'contact_person' => 'Ada Lee',
            'contact_phone' => '+852 3000 2000',
            'contact_email' => 'ada@example.com',
            'source' => 'Referral',
            'business_type' => 'Beauty Salon',
            'assigned_sales_id' => $sales->id,
            'created_by' => $sales->id,
            'status' => 'Contacted',
            'notes' => 'Interested in PLAN B.',
        ]);

        $planB = SalesPlan::where('plan_name', 'PLAN B')->first();
        $opportunity = Opportunity::updateOrCreate(['opportunity_name' => 'Harbour PLAN B New Deal'], [
            'account_id' => $account->id,
            'sales_plan_id' => $planB->id,
            'estimated_deal_amount' => $planB->selling_price,
            'probability' => 80,
            'expected_close_date' => now()->endOfMonth()->toDateString(),
            'assigned_sales_id' => $sales->id,
            'stage' => 'Done Deal',
            'notes' => 'Seed won opportunity.',
        ]);

        Deal::updateOrCreate(['opportunity_id' => $opportunity->id], [
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $planB->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $planB->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->startOfMonth()->addDays(5)->toDateString(),
            'contract_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'commission_status' => 'Pending',
            'notes' => 'Seed paid new deal.',
        ]);

        AccountDocument::updateOrCreate([
            'account_id' => $account->id,
            'type' => 'quotation',
            'document_number' => 'QT-2026-0001',
        ], [
            'opportunity_id' => $opportunity->id,
            'title' => 'PLAN B quotation',
            'amount' => $planB->selling_price,
            'status' => 'accepted',
            'document_date' => now()->startOfMonth()->addDays(1)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(14)->toDateString(),
            'notes' => 'Seed quotation for Account 360.',
        ]);

        AccountDocument::updateOrCreate([
            'account_id' => $account->id,
            'type' => 'contract',
            'document_number' => 'CT-2026-0001',
        ], [
            'opportunity_id' => $opportunity->id,
            'title' => 'PLAN B contract',
            'amount' => $planB->selling_price,
            'status' => 'signed',
            'document_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'notes' => 'Seed contract for Account 360.',
        ]);

        AccountDocument::updateOrCreate([
            'account_id' => $account->id,
            'type' => 'invoice',
            'document_number' => 'INV-2026-0001',
        ], [
            'opportunity_id' => $opportunity->id,
            'title' => 'PLAN B invoice',
            'amount' => $planB->selling_price,
            'status' => 'paid',
            'document_date' => now()->startOfMonth()->addDays(4)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(18)->toDateString(),
            'notes' => 'Seed invoice for Account 360.',
        ]);

        Deal::updateOrCreate(['notes' => 'Seed paid upgrade.'], [
            'account_id' => $account->id,
            'opportunity_id' => null,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $planC->id,
            'deal_type' => 'am_managed_upgrade',
            'deal_amount' => $planC->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->startOfMonth()->addDays(10)->toDateString(),
            'contract_date' => now()->startOfMonth()->addDays(8)->toDateString(),
            'commission_status' => 'Pending',
        ]);
    }
}
