<x-layouts::app :title="__('佣金明細')">
    @php
        $metricLabels = [
            'basic_commission' => '基本新成交佣金',
            'renewal_upgrade_commission' => '續約 / 升級佣金',
            'monthly_tier_bonus' => '每月階梯獎金',
            'high_plan_accelerator_bonus' => '高階方案加速獎金',
            'total_commission' => '總佣金',
            'pre_commission_gross_margin' => '佣金前毛利',
            'post_commission_remaining_gross_margin' => '佣金後剩餘毛利',
            'incentive_ratio' => '佣金佔毛利比例',
        ];
    @endphp
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ $run->salesUser?->name }} · {{ $run->month->format('Y-m') }}</flux:heading>
                <flux:text>狀態：{{ $run->status }}</flux:text>
            </div>
            @role('admin')
                <form method="POST" action="{{ route('commissions.approve', $run) }}">@csrf <flux:button type="submit">審批</flux:button></form>
            @endrole
        </div>
        @if(session('status'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
        <div class="grid gap-4 md:grid-cols-4">
            @foreach(['basic_commission','renewal_upgrade_commission','monthly_tier_bonus','high_plan_accelerator_bonus','total_commission','pre_commission_gross_margin','post_commission_remaining_gross_margin','incentive_ratio'] as $field)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs uppercase text-zinc-500">{{ $metricLabels[$field] ?? str($field)->replace('_',' ')->title() }}</div>
                    <div class="mt-2 text-xl font-semibold">{{ $field === 'incentive_ratio' ? number_format($run->$field * 100, 2).'%' : 'HK$'.number_format($run->$field, 2) }}</div>
                </div>
            @endforeach
        </div>
        @role('admin')
            <form method="POST" action="{{ route('commissions.override', $run) }}" class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-3">
                @csrf
                <flux:input name="override_total_commission" type="number" step="0.01" label="調整後總佣金 (HK$)" value="{{ $run->override_total_commission }}" />
                <flux:input name="override_reason" label="調整原因" value="{{ $run->override_reason }}" />
                <div class="flex items-end"><flux:button type="submit">儲存調整</flux:button></div>
            </form>
        @endrole
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800"><tr><th class="px-4 py-3">成交</th><th>類型</th><th>金額</th><th>佣金</th><th>毛利</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($run->items as $item)
                        <tr><td class="px-4 py-3">{{ $item->deal?->salesPlan?->plan_name }}</td><td>{{ $item->deal_type }}</td><td>HK${{ number_format($item->deal_amount, 2) }}</td><td>HK${{ number_format($item->total_commission, 2) }}</td><td>HK${{ number_format($item->pre_commission_gross_margin, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
