<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $deals = Deal::with(['salesPlan', 'salesUser'])->get();
        $leads = Lead::all();
        $opportunities = Opportunity::with(['assignedSales', 'salesPlan'])->get();
        $openOpportunities = $opportunities->whereNotIn('stage', ['Done Deal', 'Lost']);
        $today = today();
        $monthStart = today()->startOfMonth();
        $monthEnd = today()->endOfMonth();
        $thisMonthDeals = $deals->filter(function (Deal $deal) use ($monthStart, $monthEnd) {
            $dealDate = $deal->contract_date ?? $deal->created_at;

            return $dealDate && $dealDate->betweenIncluded($monthStart, $monthEnd);
        });

        $paidDeals = $deals->filter(fn (Deal $deal) => strtolower($deal->payment_status) === 'paid');
        $unpaidDeals = $deals->reject(fn (Deal $deal) => strtolower($deal->payment_status) === 'paid');
        $wonOpportunities = $opportunities->where('stage', 'Done Deal')->count();
        $lostOpportunities = $opportunities->where('stage', 'Lost')->count();
        $convertedLeads = $leads->whereNotNull('converted_at')->count();
        $weightedPipeline = $openOpportunities->sum(fn (Opportunity $opportunity) => (float) $opportunity->estimated_deal_amount * ((int) $opportunity->probability / 100));
        $overdueOpportunities = $openOpportunities->filter(fn (Opportunity $opportunity) => $opportunity->expected_close_date?->lt($today));
        $closingSoonOpportunities = $openOpportunities->filter(fn (Opportunity $opportunity) => $opportunity->expected_close_date?->betweenIncluded($today, $today->copy()->addDays(30)));
        $pipelineStages = collect(Opportunity::STAGES)->reject(fn (string $stage) => in_array($stage, ['Done Deal', 'Lost'], true));

        return view('reports.index', [
            'metrics' => [
                'total_sales' => (float) $deals->sum('deal_amount'),
                'this_month_sales' => (float) $thisMonthDeals->sum('deal_amount'),
                'paid_sales' => (float) $paidDeals->sum('deal_amount'),
                'unpaid_sales' => (float) $unpaidDeals->sum('deal_amount'),
                'open_pipeline' => (float) $openOpportunities->sum('estimated_deal_amount'),
                'weighted_pipeline' => (float) $weightedPipeline,
                'average_deal_size' => (float) $deals->avg('deal_amount'),
                'lead_conversion_rate' => $leads->count() > 0 ? round($convertedLeads / $leads->count() * 100, 1) : 0,
                'win_rate' => ($wonOpportunities + $lostOpportunities) > 0 ? round($wonOpportunities / ($wonOpportunities + $lostOpportunities) * 100, 1) : 0,
                'overdue_pipeline_count' => $overdueOpportunities->count(),
                'overdue_pipeline_value' => (float) $overdueOpportunities->sum('estimated_deal_amount'),
                'closing_soon_count' => $closingSoonOpportunities->count(),
                'closing_soon_value' => (float) $closingSoonOpportunities->sum('estimated_deal_amount'),
            ],
            'insights' => [
                [
                    'label' => '收款風險',
                    'value' => 'HK$'.number_format((float) $unpaidDeals->sum('deal_amount')),
                    'detail' => $unpaidDeals->count().' 宗成交尚未標記 Paid，會影響現金回收及佣金計算。',
                    'tone' => $unpaidDeals->isNotEmpty() ? 'amber' : 'teal',
                ],
                [
                    'label' => 'Forecast 可見度',
                    'value' => 'HK$'.number_format((float) $weightedPipeline),
                    'detail' => '按機會成功率加權，來自 HK$'.number_format((float) $openOpportunities->sum('estimated_deal_amount')).' open pipeline。',
                    'tone' => 'sky',
                ],
                [
                    'label' => 'Pipeline Hygiene',
                    'value' => $overdueOpportunities->count().' 宗',
                    'detail' => '預計成交日已過期，總值 HK$'.number_format((float) $overdueOpportunities->sum('estimated_deal_amount')).'，建議管理層每週檢視。',
                    'tone' => $overdueOpportunities->isNotEmpty() ? 'rose' : 'teal',
                ],
                [
                    'label' => '30 日內可推進',
                    'value' => 'HK$'.number_format((float) $closingSoonOpportunities->sum('estimated_deal_amount')),
                    'detail' => $closingSoonOpportunities->count().' 宗 open opportunity 預計 30 日內 close。',
                    'tone' => 'violet',
                ],
            ],
            'dealsByPlan' => $deals
                ->groupBy(fn (Deal $deal) => $deal->salesPlan?->plan_name ?? '未指定方案')
                ->map(fn ($group) => (float) $group->sum('deal_amount'))
                ->sortDesc(),
            'pipeline' => $pipelineStages
                ->mapWithKeys(fn (string $stage) => [$stage => (float) $openOpportunities->where('stage', $stage)->sum('estimated_deal_amount')]),
            'topSalesUsers' => $deals
                ->groupBy(fn (Deal $deal) => $deal->salesUser?->name ?? '未指定銷售')
                ->map(fn ($group) => (float) $group->sum('deal_amount'))
                ->sortDesc()
                ->take(6),
            'salesLeaderboard' => $deals
                ->groupBy(fn (Deal $deal) => $deal->salesUser?->name ?? '未指定銷售')
                ->map(fn ($group, string $name) => [
                    'name' => $name,
                    'deals' => $group->count(),
                    'sales' => (float) $group->sum('deal_amount'),
                    'paid' => (float) $group->filter(fn (Deal $deal) => strtolower($deal->payment_status) === 'paid')->sum('deal_amount'),
                    'average' => (float) $group->avg('deal_amount'),
                ])
                ->sortByDesc('sales')
                ->take(5)
                ->values(),
            'planMix' => $deals
                ->groupBy(fn (Deal $deal) => $deal->salesPlan?->plan_name ?? '未指定方案')
                ->map(fn ($group, string $name) => [
                    'name' => $name,
                    'deals' => $group->count(),
                    'sales' => (float) $group->sum('deal_amount'),
                    'average' => (float) $group->avg('deal_amount'),
                ])
                ->sortByDesc('sales')
                ->take(5)
                ->values(),
        ]);
    }

    public function export(Request $request, string $type)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $rows = match ($type) {
            'commission' => Deal::with(['salesUser', 'salesPlan'])->get()->map(fn ($deal) => [
                $deal->salesUser?->name,
                $deal->salesPlan?->plan_name,
                $deal->deal_type,
                $deal->deal_amount,
                $deal->payment_status,
                optional($deal->payment_date)->toDateString(),
            ]),
            default => Deal::with(['account', 'salesUser', 'salesPlan'])->get()->map(fn ($deal) => [
                $deal->account?->company_name,
                $deal->salesUser?->name,
                $deal->salesPlan?->plan_name,
                $deal->deal_type,
                $deal->deal_amount,
            ]),
        };

        $csv = $rows->map(fn ($row) => collect($row)->map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"')->implode(','))->implode("\n");

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$type.'-report.csv"',
        ]);
    }
}
