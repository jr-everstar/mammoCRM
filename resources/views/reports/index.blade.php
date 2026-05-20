<x-layouts::app :title="__('報表')">
    @php
        $insightToneClasses = [
            'amber' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-100',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-100',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-900/70 dark:bg-sky-950/30 dark:text-sky-100',
            'teal' => 'border-teal-200 bg-teal-50 text-teal-900 dark:border-teal-900/70 dark:bg-teal-950/30 dark:text-teal-100',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-900 dark:border-violet-900/70 dark:bg-violet-950/30 dark:text-violet-100',
        ];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">報表</flux:heading>
                <flux:text>管理層總覽銷售、現金回收、Forecast、Pipeline 風險及方案表現。</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('reports.export', 'sales')" target="_blank">匯出銷售 CSV</flux:button>
                <flux:button :href="route('reports.export', 'commission')" target="_blank">匯出佣金 CSV</flux:button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($insights as $insight)
                <div class="rounded-xl border p-4 {{ $insightToneClasses[$insight['tone']] ?? $insightToneClasses['sky'] }}">
                    <div class="text-sm font-semibold">{{ $insight['label'] }}</div>
                    <div class="mt-2 text-2xl font-semibold">{{ $insight['value'] }}</div>
                    <div class="mt-2 text-sm opacity-80">{{ $insight['detail'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="mc-stat">
                <div class="text-sm text-slate-500">累計銷售額</div>
                <div class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">HK${{ number_format($metrics['total_sales']) }}</div>
            </div>
            <div class="mc-stat">
                <div class="text-sm text-slate-500">本月成交額</div>
                <div class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">HK${{ number_format($metrics['this_month_sales']) }}</div>
            </div>
            <div class="mc-stat">
                <div class="text-sm text-slate-500">Lead 轉換率</div>
                <div class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $metrics['lead_conversion_rate'] }}%</div>
            </div>
            <div class="mc-stat">
                <div class="text-sm text-slate-500">Win Rate</div>
                <div class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $metrics['win_rate'] }}%</div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="mc-panel p-5 lg:col-span-2">
                <flux:heading>管理層焦點</flux:heading>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                        <div class="text-sm text-slate-500">Paid Sales</div>
                        <div class="mt-2 text-xl font-semibold">HK${{ number_format($metrics['paid_sales']) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                        <div class="text-sm text-slate-500">Open Pipeline</div>
                        <div class="mt-2 text-xl font-semibold">HK${{ number_format($metrics['open_pipeline']) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                        <div class="text-sm text-slate-500">平均 Deal Size</div>
                        <div class="mt-2 text-xl font-semibold">HK${{ number_format($metrics['average_deal_size']) }}</div>
                    </div>
                </div>
                <div class="mt-5 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">銷售</th>
                                <th class="px-4 py-3">Deals</th>
                                <th class="px-4 py-3">Sales</th>
                                <th class="px-4 py-3">Paid</th>
                                <th class="px-4 py-3">Avg</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse ($salesLeaderboard as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3">{{ $row['deals'] }}</td>
                                    <td class="px-4 py-3">HK${{ number_format($row['sales']) }}</td>
                                    <td class="px-4 py-3">HK${{ number_format($row['paid']) }}</td>
                                    <td class="px-4 py-3">HK${{ number_format($row['average']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-5 text-center text-slate-500">暫未有成交資料</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mc-panel p-5">
                <flux:heading>方案組合</flux:heading>
                <div class="mt-4 space-y-3">
                    @forelse ($planMix as $plan)
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-800">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium">{{ $plan['name'] }}</div>
                                <div class="text-sm text-slate-500">{{ $plan['deals'] }} deals</div>
                            </div>
                            <div class="mt-2 text-lg font-semibold">HK${{ number_format($plan['sales']) }}</div>
                            <div class="text-sm text-slate-500">Avg HK${{ number_format($plan['average']) }}</div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-slate-200 p-5 text-sm text-slate-500 dark:border-slate-800">暫未有方案銷售資料</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="mc-panel p-5">
                <flux:heading>按方案成交</flux:heading>
                <div class="mt-4 h-72">
                    <canvas id="dealsByPlan" data-report-chart data-chart-type="bar" data-chart-label="HK$" data-chart-color="#2563eb"></canvas>
                </div>
                <script type="application/json" id="dealsByPlanData">@json($dealsByPlan)</script>
            </div>
            <div class="mc-panel p-5">
                <flux:heading>Open Pipeline 預計金額</flux:heading>
                <div class="mt-4 h-72">
                    <canvas id="pipeline" data-report-chart data-chart-type="doughnut" data-chart-label="HK$"></canvas>
                </div>
                <script type="application/json" id="pipelineData">@json($pipeline)</script>
            </div>
            <div class="mc-panel p-5">
                <flux:heading>Top Sales</flux:heading>
                <div class="mt-4 h-72">
                    <canvas id="topSalesUsers" data-report-chart data-chart-type="bar" data-chart-label="HK$" data-chart-color="#0f766e"></canvas>
                </div>
                <script type="application/json" id="topSalesUsersData">@json($topSalesUsers)</script>
            </div>
        </div>
    </div>
</x-layouts::app>
