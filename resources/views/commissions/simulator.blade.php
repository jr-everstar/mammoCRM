<x-layouts::app :title="__('佣金模擬器')">
    <div class="space-y-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">佣金模擬器</flux:heading>
                <flux:text>手動輸入已簽約方案數量，預覽佣金規則結果，不會建立佣金紀錄或更新成交狀態。</flux:text>
            </div>
            <flux:button :href="route('commissions.index')" variant="ghost" icon="arrow-left" wire:navigate>返回佣金計算</flux:button>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('commissions.simulate') }}" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                @foreach($salesPlans as $plan)
                    <flux:input
                        type="number"
                        min="0"
                        step="1"
                        name="plan_counts[{{ $plan->id }}]"
                        label="{{ $plan->plan_name }}"
                        value="{{ old('plan_counts.'.$plan->id, $simulation['plan_counts'][$plan->id] ?? 0) }}"
                    />
                @endforeach
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="admin_monthly_tier_override" value="1" @checked(old('admin_monthly_tier_override', $simulation['admin_monthly_tier_override'] ?? false))>
                    強制啟用階梯獎金
                </label>
                <flux:button type="submit" icon="calculator">執行模擬</flux:button>
            </div>
        </form>

        @if($simulation)
            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">簽約方案</div><div class="mt-2 text-2xl font-semibold">{{ $simulation['signed_plan_count'] }}</div></div>
                <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">簽約銷售額</div><div class="mt-2 text-2xl font-semibold">HK${{ number_format($simulation['signed_sales_amount'], 2) }}</div></div>
                <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">佣金前毛利</div><div class="mt-2 text-2xl font-semibold">HK${{ number_format($simulation['pre_commission_gross_margin'], 2) }}</div></div>
                <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">總佣金</div><div class="mt-2 text-2xl font-semibold">HK${{ number_format($simulation['total_commission'], 2) }}</div></div>
                <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">營運成本緩衝 (25%)</div><div class="mt-2 text-2xl font-semibold">HK${{ number_format($simulation['operation_cost_buffer'], 2) }}</div></div>
                <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">公司淨利潤</div><div class="mt-2 text-2xl font-semibold">HK${{ number_format($simulation['company_net_profit'], 2) }}</div></div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-slate-500">基本新成交佣金</div>
                    <div class="mt-1 text-xl font-semibold">HK${{ number_format($simulation['basic_commission'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-slate-500">續約 / 升級佣金</div>
                    <div class="mt-1 text-xl font-semibold">HK${{ number_format($simulation['renewal_upgrade_commission'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-slate-500">階梯獎金</div>
                    <div class="mt-1 text-xl font-semibold">HK${{ number_format($simulation['monthly_tier_bonus'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-slate-500">高方案加速獎金</div>
                    <div class="mt-1 text-xl font-semibold">HK${{ number_format($simulation['high_plan_accelerator_bonus'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-slate-500">合資格銷售額</div>
                    <div class="mt-1 text-xl font-semibold">HK${{ number_format($simulation['monthly_qualified_sales_amount'], 2) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-slate-500">佣金後剩餘毛利</div>
                    <div class="mt-1 text-xl font-semibold">HK${{ number_format($simulation['post_commission_remaining_gross_margin'], 2) }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3">方案</th>
                            <th class="px-4 py-3">數量</th>
                            <th class="px-4 py-3">金額</th>
                            <th class="px-4 py-3">基本佣金</th>
                            <th class="px-4 py-3">佣金前毛利</th>
                            <th class="px-4 py-3">單項佣金</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($simulation['items'] as $item)
                            <tr>
                                <td class="px-4 py-3">{{ $item['plan']->plan_name }}</td>
                                <td class="px-4 py-3">{{ $item['count'] }}</td>
                                <td class="px-4 py-3">HK${{ number_format($item['deal_amount'], 2) }}</td>
                                <td class="px-4 py-3">HK${{ number_format($item['basic_commission'], 2) }}</td>
                                <td class="px-4 py-3">HK${{ number_format($item['pre_commission_gross_margin'], 2) }}</td>
                                <td class="px-4 py-3">HK${{ number_format($item['total_commission'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">輸入至少一個方案數量即可預覽。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts::app>
