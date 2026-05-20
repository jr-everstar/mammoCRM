<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Models\CostConfig;
use App\Models\HighPlanAcceleratorRule;
use App\Models\MonthlyTierRule;
use App\Models\OpportunityStageRule;
use App\Models\RenewalUpgradeRule;
use App\Models\SalesPlan;
use App\Models\TrialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigController extends Controller
{
    public function index(string $type): View
    {
        return view('config.index', [
            'type' => $type,
            'title' => $this->title($type),
            'records' => $this->records($type),
            'salesPlans' => SalesPlan::orderBy('display_order')->get(),
            'planCommissions' => CommissionRule::where('key', 'like', 'new_deal_commission_%')->pluck('value', 'sales_plan_id'),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $data = $request->validate($this->rules($type, creating: true));

        foreach (['is_active', 'can_trigger_monthly_tier', 'hpa_eligible'] as $booleanField) {
            if (array_key_exists($booleanField, $this->rules($type, creating: true))) {
                $data[$booleanField] = (bool) ($data[$booleanField] ?? false);
            }
        }

        if ($type === 'sales-plans') {
            $commission = $data['new_deal_commission'] ?? 0;
            unset($data['new_deal_commission']);
            $data['plan_code'] = $this->generatePlanCode();
            $plan = SalesPlan::create($data);
            $this->syncPlanCommissionRule($plan, $commission);

            return back()->with('status', '銷售方案及新成交佣金已建立。');
        }

        if ($type === 'high-accelerators') {
            $requirements = $data['hpa_plan_quantities'] ?? [];
            unset($data['hpa_plan_quantities']);
            if (array_sum(array_map('intval', $requirements)) <= 0) {
                return back()->withErrors(['hpa_plan_quantities' => '請至少設定一個 HPA opt-in plan QTY。'])->withInput();
            }
            $data['code'] = $this->generateHpaRuleCode();
            $data['min_hpa_eligible_deals'] = array_sum(array_map('intval', $requirements));
            $rule = HighPlanAcceleratorRule::create($data);
            $this->syncHpaPlanRequirements($rule, $requirements);

            return back()->with('status', 'HPA rule created.');
        }

        $this->model($type)::create($data);

        return back()->with('status', 'Configuration created.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->model($type);
        $record = $model::findOrFail($id);
        $data = $request->validate($this->rules($type));

        foreach (['is_active', 'can_trigger_monthly_tier', 'hpa_eligible'] as $booleanField) {
            if (array_key_exists($booleanField, $this->rules($type))) {
                $data[$booleanField] = (bool) ($data[$booleanField] ?? false);
            }
        }

        if ($type === 'sales-plans') {
            $commission = $data['new_deal_commission'] ?? 0;
            unset($data['new_deal_commission']);
            $record->update($data);
            $this->syncPlanCommissionRule($record, $commission);

            return back()->with('status', '銷售方案及新成交佣金已更新。');
        }

        if ($type === 'high-accelerators') {
            $requirements = $data['hpa_plan_quantities'] ?? [];
            unset($data['hpa_plan_quantities']);
            if (array_sum(array_map('intval', $requirements)) <= 0) {
                return back()->withErrors(['hpa_plan_quantities' => '請至少設定一個 HPA opt-in plan QTY。'])->withInput();
            }
            $data['min_hpa_eligible_deals'] = array_sum(array_map('intval', $requirements));
            $record->update($data);
            $this->syncHpaPlanRequirements($record, $requirements);

            return back()->with('status', 'HPA rule updated.');
        }

        $record->update($data);

        return back()->with('status', 'Configuration updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        abort_unless($type === 'high-accelerators', 404);

        HighPlanAcceleratorRule::findOrFail($id)->delete();

        return back()->with('status', 'HPA rule deleted.');
    }

    private function model(string $type): string
    {
        return match ($type) {
            'sales-plans' => SalesPlan::class,
            'cost-configs' => CostConfig::class,
            'commission-rules' => CommissionRule::class,
            'opportunity-stage-rules' => OpportunityStageRule::class,
            'monthly-tiers' => MonthlyTierRule::class,
            'high-accelerators' => HighPlanAcceleratorRule::class,
            'renewal-upgrade-rules' => RenewalUpgradeRule::class,
            'trial-settings' => TrialSetting::class,
            default => abort(404),
        };
    }

    private function title(string $type): string
    {
        return str($type)->replace('-', ' ')->title()->toString();
    }

    private function rules(string $type, bool $creating = false): array
    {
        return match ($type) {
            'sales-plans' => [
                'plan_name' => ['required', 'string'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'report_commitment' => ['required', 'integer', 'min:0'],
                'average_cost_per_report' => ['required', 'numeric', 'min:0'],
                'included_ipad_quantity' => ['required', 'integer', 'min:0'],
                'included_sensor_set_quantity' => ['required', 'integer', 'min:0'],
                'new_deal_commission' => ['required', 'numeric', 'min:0'],
                'monthly_tier_count_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'can_trigger_monthly_tier' => ['nullable', 'boolean'],
                'hpa_eligible' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
                'display_order' => ['required', 'integer', 'min:0'],
            ],
            'cost-configs' => ['name' => ['required', 'string'], 'value' => ['required', 'numeric'], 'unit' => ['required', 'string'], 'description' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']],
            'commission-rules' => ['name' => ['required', 'string'], 'type' => ['required', 'string'], 'value' => ['required', 'numeric'], 'is_active' => ['nullable', 'boolean'], 'description' => ['nullable', 'string']],
            'opportunity-stage-rules' => ['label' => ['required', 'string'], 'probability' => ['required', 'integer', 'min:0', 'max:100'], 'guidance' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'], 'display_order' => ['required', 'integer', 'min:0']],
            'monthly-tiers' => ['threshold_amount' => ['required', 'numeric'], 'tier_bonus' => ['required', 'numeric'], 'cumulative_bonus' => ['required', 'numeric'], 'is_active' => ['nullable', 'boolean']],
            'high-accelerators' => ['name' => ['required', 'string'], 'hpa_plan_quantities' => ['nullable', 'array'], 'hpa_plan_quantities.*' => ['nullable', 'integer', 'min:0'], 'bonus' => ['required', 'numeric'], 'priority' => ['required', 'integer'], 'is_active' => ['nullable', 'boolean']],
            'renewal-upgrade-rules' => ['name' => ['required', 'string'], 'commission_rate' => ['required', 'numeric'], 'monthly_tier_count_percentage' => ['required', 'numeric'], 'can_trigger_monthly_tier' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'], 'definition' => ['nullable', 'string']],
            'trial-settings' => ['name' => ['required', 'string'], 'value' => ['nullable', 'string'], 'description' => ['nullable', 'string']],
        };
    }

    private function records(string $type)
    {
        $query = $this->model($type)::query();

        if ($type === 'high-accelerators') {
            $query->with('salesPlanRequirements');
        }

        return $query->orderBy('id')->paginate(20);
    }

    private function syncPlanCommissionRule(SalesPlan $plan, float|int|string $commission): void
    {
        CommissionRule::updateOrCreate(
            ['key' => 'new_deal_commission_'.$plan->plan_code],
            [
                'name' => $plan->plan_name.' 新成交佣金',
                'type' => 'amount',
                'value' => $commission,
                'sales_plan_id' => $plan->id,
                'is_active' => true,
                'description' => '由銷售方案設定自動維護。',
            ]
        );
    }

    private function generatePlanCode(): string
    {
        $nextNumber = 1;

        SalesPlan::query()
            ->where('plan_code', 'like', 'PLAN-%')
            ->pluck('plan_code')
            ->each(function (string $code) use (&$nextNumber): void {
                if (preg_match('/^PLAN-(\d+)$/', $code, $matches)) {
                    $nextNumber = max($nextNumber, ((int) $matches[1]) + 1);
                }
            });

        do {
            $code = 'PLAN-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (SalesPlan::where('plan_code', $code)->exists());

        return $code;
    }

    private function generateHpaRuleCode(): string
    {
        $nextNumber = 1;

        HighPlanAcceleratorRule::query()
            ->where('code', 'like', 'HPA-%')
            ->pluck('code')
            ->each(function (string $code) use (&$nextNumber): void {
                if (preg_match('/^HPA-(\d+)$/', $code, $matches)) {
                    $nextNumber = max($nextNumber, ((int) $matches[1]) + 1);
                }
            });

        do {
            $code = 'HPA-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (HighPlanAcceleratorRule::where('code', $code)->exists());

        return $code;
    }

    private function syncHpaPlanRequirements(HighPlanAcceleratorRule $rule, array $requirements): void
    {
        $sync = [];

        foreach ($requirements as $salesPlanId => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity > 0 && SalesPlan::whereKey($salesPlanId)->where('hpa_eligible', true)->exists()) {
                $sync[$salesPlanId] = ['required_quantity' => $quantity];
            }
        }

        $rule->salesPlanRequirements()->sync($sync);
    }
}
