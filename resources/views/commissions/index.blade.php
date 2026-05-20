<x-layouts::app :title="__('佣金計算')">
    <div class="space-y-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl">佣金計算</flux:heading>
                <flux:text>按月份計算佣金，Admin 可審批及調整；銷售只可查看自己的結果。</flux:text>
            </div>
            @if(auth()->user()->hasAnyRole(['admin', 'staff']))
                <flux:button :href="route('commissions.simulator')" icon="beaker" wire:navigate>佣金模擬器</flux:button>
            @endif
        </div>
        @if(session('status'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
        @role('admin')
            <form method="POST" action="{{ route('commissions.run') }}" class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-4">
                @csrf
                <flux:input type="month" name="month" label="月份" value="{{ now()->format('Y-m') }}" required />
                <flux:select name="sales_user_id" label="銷售人員">@foreach($salesUsers as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</flux:select>
                <label class="flex items-end gap-2 pb-2 text-sm"><input type="checkbox" name="admin_monthly_tier_override" value="1"> Admin 強制啟用階梯獎金</label>
                <div class="flex items-end"><flux:button type="submit">執行計算</flux:button></div>
            </form>
        @endrole
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800"><tr><th class="px-4 py-3">月份</th><th class="px-4 py-3">銷售</th><th class="px-4 py-3">總佣金</th><th class="px-4 py-3">狀態</th><th></th></tr></thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($runs as $run)
                        <tr>
                            <td class="px-4 py-3">{{ $run->month->format('Y-m') }}</td>
                            <td class="px-4 py-3">{{ $run->salesUser?->name }}</td>
                            <td class="px-4 py-3">HK${{ number_format($run->override_total_commission ?? $run->total_commission, 2) }}</td>
                            <td class="px-4 py-3">{{ $run->status }}</td>
                            <td class="px-4 py-3 text-right"><flux:button size="sm" variant="ghost" :href="route('commissions.show', $run)" wire:navigate>查看</flux:button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $runs->links() }}
    </div>
</x-layouts::app>
