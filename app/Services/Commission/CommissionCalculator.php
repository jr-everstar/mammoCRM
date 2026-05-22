<?php

namespace App\Services\Commission;

use App\Models\CommissionRule;
use App\Models\CommissionRun;
use App\Models\CostConfig;
use App\Models\Deal;
use App\Models\HighPlanAcceleratorRule;
use App\Models\MonthlyTierRule;
use App\Models\RenewalUpgradeRule;
use App\Models\SalesPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionCalculator
{
    public function calculate(User $salesUser, string $month, bool $adminMonthlyTierOverride = false): CommissionRun
    {
        $monthDate = CarbonImmutable::parse($month)->startOfMonth();

        return DB::transaction(function () use ($salesUser, $monthDate, $adminMonthlyTierOverride) {
            $deals = $this->eligibleDeals($salesUser, $monthDate);

            $run = CommissionRun::updateOrCreate(
                ['month' => $monthDate->toDateTimeString(), 'sales_user_id' => $salesUser->id],
                ['status' => 'Calculated', 'admin_monthly_tier_override' => $adminMonthlyTierOverride]
            );
            $run->items()->delete();

            $totals = [
                'monthly_qualified_sales_amount' => 0.0,
                'basic_commission' => 0.0,
                'renewal_upgrade_commission' => 0.0,
                'pre_commission_gross_margin' => 0.0,
            ];

            foreach ($deals as $deal) {
                $item = $this->calculateDeal($deal);
                $totals['basic_commission'] += $item['basic_commission'];
                $totals['renewal_upgrade_commission'] += $item['renewal_upgrade_commission'];
                $totals['monthly_qualified_sales_amount'] += $item['monthly_tier_amount'];
                $totals['pre_commission_gross_margin'] += $item['pre_commission_gross_margin'];

                $run->items()->create([
                    'deal_id' => $deal->id,
                    'deal_type' => $deal->deal_type,
                    'deal_amount' => $deal->deal_amount,
                    'basic_commission' => $item['basic_commission'],
                    'renewal_upgrade_commission' => $item['renewal_upgrade_commission'],
                    'pre_commission_gross_margin' => $item['pre_commission_gross_margin'],
                    'total_commission' => $item['basic_commission'] + $item['renewal_upgrade_commission'],
                    'metadata' => $item['metadata'],
                ]);
            }

            $monthlyTierBonus = $this->monthlyTierBonus($deals, $totals['monthly_qualified_sales_amount'], $adminMonthlyTierOverride);
            $hpaBonus = $this->highPlanAcceleratorBonus($deals);
            $totalCommission = $totals['basic_commission'] + $totals['renewal_upgrade_commission'] + $monthlyTierBonus + $hpaBonus;
            $preMargin = $totals['pre_commission_gross_margin'];

            $run->update([
                'monthly_qualified_sales_amount' => $totals['monthly_qualified_sales_amount'],
                'basic_commission' => $totals['basic_commission'],
                'renewal_upgrade_commission' => $totals['renewal_upgrade_commission'],
                'monthly_tier_bonus' => $monthlyTierBonus,
                'high_plan_accelerator_bonus' => $hpaBonus,
                'total_commission' => $totalCommission,
                'pre_commission_gross_margin' => $preMargin,
                'post_commission_remaining_gross_margin' => $preMargin - $totalCommission,
                'incentive_ratio' => $preMargin > 0 ? $totalCommission / $preMargin : 0,
            ]);

            $deals->each->update(['commission_status' => 'Calculated']);

            return $run->fresh(['items.deal.salesPlan', 'salesUser']);
        });
    }

    public function simulate(array $signedPlanCounts, bool $adminMonthlyTierOverride = false): array
    {
        $operationCostBufferPercentage = 25.0;
        $plans = SalesPlan::query()
            ->where('is_active', true)
            ->whereIn('id', array_keys($signedPlanCounts))
            ->get()
            ->keyBy('id');
        $deals = collect();
        $totals = [
            'monthly_qualified_sales_amount' => 0.0,
            'basic_commission' => 0.0,
            'renewal_upgrade_commission' => 0.0,
            'pre_commission_gross_margin' => 0.0,
        ];
        $items = collect();
        $signedSalesAmount = 0.0;

        foreach ($signedPlanCounts as $planId => $count) {
            $plan = $plans->get((int) $planId);

            if (! $plan || (int) $count < 1) {
                continue;
            }

            foreach (range(1, (int) $count) as $index) {
                $signedSalesAmount += (float) $plan->selling_price;
                $deal = new Deal([
                    'sales_plan_id' => $plan->id,
                    'deal_type' => 'new_deal',
                    'deal_amount' => $plan->selling_price,
                    'payment_status' => 'Paid',
                    'commission_status' => 'Pending',
                ]);
                $deal->setRelation('salesPlan', $plan);
                $deals->push($deal);
            }
        }

        foreach ($deals as $deal) {
            $item = $this->calculateDeal($deal);
            $totals['basic_commission'] += $item['basic_commission'];
            $totals['renewal_upgrade_commission'] += $item['renewal_upgrade_commission'];
            $totals['monthly_qualified_sales_amount'] += $item['monthly_tier_amount'];
            $totals['pre_commission_gross_margin'] += $item['pre_commission_gross_margin'];

            $existing = $items->get($deal->sales_plan_id, [
                'plan' => $deal->salesPlan,
                'count' => 0,
                'deal_amount' => 0.0,
                'basic_commission' => 0.0,
                'renewal_upgrade_commission' => 0.0,
                'pre_commission_gross_margin' => 0.0,
                'total_commission' => 0.0,
            ]);

            $existing['count']++;
            $existing['deal_amount'] += (float) $deal->deal_amount;
            $existing['basic_commission'] += $item['basic_commission'];
            $existing['renewal_upgrade_commission'] += $item['renewal_upgrade_commission'];
            $existing['pre_commission_gross_margin'] += $item['pre_commission_gross_margin'];
            $existing['total_commission'] += $item['basic_commission'] + $item['renewal_upgrade_commission'];

            $items->put($deal->sales_plan_id, $existing);
        }

        $monthlyTierBonus = $this->monthlyTierBonus($deals, $totals['monthly_qualified_sales_amount'], $adminMonthlyTierOverride);
        $hpaBonus = $this->highPlanAcceleratorBonus($deals);
        $totalCommission = $totals['basic_commission'] + $totals['renewal_upgrade_commission'] + $monthlyTierBonus + $hpaBonus;
        $preMargin = $totals['pre_commission_gross_margin'];
        $postCommissionMargin = $preMargin - $totalCommission;
        $operationCostBuffer = round($signedSalesAmount * $operationCostBufferPercentage / 100, 2);

        return [
            'admin_monthly_tier_override' => $adminMonthlyTierOverride,
            'operation_cost_buffer_percentage' => $operationCostBufferPercentage,
            'signed_plan_count' => $deals->count(),
            'signed_sales_amount' => $signedSalesAmount,
            'plan_counts' => collect($signedPlanCounts)->map(fn ($count) => (int) $count)->all(),
            'monthly_qualified_sales_amount' => $totals['monthly_qualified_sales_amount'],
            'basic_commission' => $totals['basic_commission'],
            'renewal_upgrade_commission' => $totals['renewal_upgrade_commission'],
            'monthly_tier_bonus' => $monthlyTierBonus,
            'high_plan_accelerator_bonus' => $hpaBonus,
            'total_commission' => $totalCommission,
            'pre_commission_gross_margin' => $preMargin,
            'post_commission_remaining_gross_margin' => $postCommissionMargin,
            'operation_cost_buffer' => $operationCostBuffer,
            'company_net_profit' => $postCommissionMargin - $operationCostBuffer,
            'incentive_ratio' => $preMargin > 0 ? $totalCommission / $preMargin : 0,
            'items' => $items->values(),
        ];
    }

    public function calculateDeal(Deal $deal): array
    {
        $plan = $deal->salesPlan;
        $preMargin = $this->preCommissionGrossMargin($deal);

        if ($deal->deal_type === 'new_deal') {
            $leadTrialTotal = $this->rule('lead_in_reward') + $this->rule('trial_reward');
            $doneDealCommission = $plan ? $this->rule('new_deal_commission_'.$plan->plan_code) : 0;
            $monthlyTierCountPercentage = (float) ($plan?->monthly_tier_count_percentage ?? 0);

            return [
                'basic_commission' => $leadTrialTotal + $doneDealCommission,
                'renewal_upgrade_commission' => 0,
                'monthly_tier_amount' => round(((float) $deal->deal_amount) * $monthlyTierCountPercentage / 100, 2),
                'pre_commission_gross_margin' => $preMargin,
                'metadata' => [
                    'initial_lead_trial_reward' => round($leadTrialTotal * ($this->rule('initial_payout_percentage') / 100), 2),
                    'completion_lead_trial_reward' => round($leadTrialTotal * ($this->rule('completion_payout_percentage') / 100), 2),
                    'done_deal_commission' => $doneDealCommission,
                    'monthly_tier_count_percentage' => $monthlyTierCountPercentage,
                ],
            ];
        }

        $renewalRule = RenewalUpgradeRule::where('deal_type', $deal->deal_type)->where('is_active', true)->first();
        $rate = (float) ($renewalRule?->commission_rate ?? 0);
        $countIn = (float) ($renewalRule?->monthly_tier_count_percentage ?? 0);

        return [
            'basic_commission' => 0,
            'renewal_upgrade_commission' => round(((float) $deal->deal_amount) * $rate / 100, 2),
            'monthly_tier_amount' => round(((float) $deal->deal_amount) * $countIn / 100, 2),
            'pre_commission_gross_margin' => $preMargin,
            'metadata' => ['commission_rate' => $rate, 'monthly_tier_count_percentage' => $countIn],
        ];
    }

    private function eligibleDeals(User $salesUser, CarbonImmutable $monthDate): Collection
    {
        return Deal::query()
            ->with('salesPlan')
            ->where('sales_user_id', $salesUser->id)
            ->where('payment_status', 'Paid')
            ->whereNotIn('payment_status', ['Cancelled', 'Refunded'])
            ->whereBetween('payment_date', [$monthDate->toDateString(), $monthDate->endOfMonth()->toDateString()])
            ->get();
    }

    private function preCommissionGrossMargin(Deal $deal): float
    {
        $plan = $deal->salesPlan;
        if (! $plan) {
            return (float) $deal->deal_amount;
        }

        $reportCost = $plan->report_commitment * $this->cost('report_cost_per_report');
        $hardwareCost = ($plan->included_ipad_quantity * $this->cost('managed_ipad_cost'))
            + ($plan->included_sensor_set_quantity * $this->cost('sensor_set_cost'));

        return (float) $deal->deal_amount - $reportCost - $hardwareCost;
    }

    private function monthlyTierBonus(Collection $deals, float $qualifiedAmount, bool $override): float
    {
        $hasQualifyingNewDeal = $deals->contains(function (Deal $deal) {
            return $deal->deal_type === 'new_deal'
                && (bool) $deal->salesPlan?->can_trigger_monthly_tier;
        });

        if (! $hasQualifyingNewDeal && ! $override) {
            return 0;
        }

        return (float) (MonthlyTierRule::query()
            ->where('is_active', true)
            ->where('threshold_amount', '<=', $qualifiedAmount)
            ->orderByDesc('threshold_amount')
            ->first()?->cumulative_bonus ?? 0);
    }

    private function highPlanAcceleratorBonus(Collection $deals): float
    {
        $eligibleDeals = $deals
            ->filter(fn (Deal $deal) => $deal->deal_type === 'new_deal' && (bool) $deal->salesPlan?->hpa_eligible)
            ->values();
        $eligibleDealCount = $eligibleDeals->count();
        $eligibleDealCountsByPlan = $eligibleDeals->countBy('sales_plan_id');

        return (float) (HighPlanAcceleratorRule::query()
            ->with('salesPlanRequirements')
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByDesc('min_hpa_eligible_deals')
            ->get()
            ->first(function (HighPlanAcceleratorRule $rule) use ($eligibleDealCount, $eligibleDealCountsByPlan) {
                if ($rule->salesPlanRequirements->isEmpty()) {
                    return $rule->min_hpa_eligible_deals <= $eligibleDealCount;
                }

                return $rule->salesPlanRequirements->every(function ($plan) use ($eligibleDealCountsByPlan) {
                    return (int) ($eligibleDealCountsByPlan[$plan->id] ?? 0) >= (int) $plan->pivot->required_quantity;
                });
            })?->bonus ?? 0);
    }

    private function rule(string $key): float
    {
        return (float) (CommissionRule::where('key', $key)->where('is_active', true)->value('value') ?? 0);
    }

    private function cost(string $key): float
    {
        return (float) (CostConfig::where('key', $key)->where('is_active', true)->value('value') ?? 0);
    }
}
