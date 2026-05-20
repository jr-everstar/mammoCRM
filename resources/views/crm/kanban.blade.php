<x-layouts::app :title="__('銷售看板')">
    @php
        $stageLabels = [
            'Lead-in' => '初步接洽',
            'Meeting / Demo' => '會議 / 示範',
            'Trial' => '試用',
            'Proposal' => '報價',
            'Negotiation' => '洽談',
            'Done Deal' => '已成交',
            'Lost' => '已流失',
        ];
        $stageHelp = [
            'Lead-in' => '確認需求、預算、決策人',
            'Meeting / Demo' => '示範及需求訪談',
            'Trial' => '試用安排與結果跟進',
            'Proposal' => '方案、金額及硬件安排',
            'Negotiation' => '付款與條款洽談',
            'Done Deal' => '已簽約，建立 Deal',
            'Lost' => '記錄流失原因',
        ];
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">機會 (OP) 看板</flux:heading>
                <flux:text>用拖拉方式推進 OP。移到已成交會要求成交資料；移到已流失會要求流失原因。</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" :href="route('crm.index', 'opportunities')" wire:navigate>列表檢視</flux:button>
                <flux:button :href="route('crm.create', 'opportunities')" wire:navigate>新增機會</flux:button>
            </div>
        </div>

        <div class="mc-panel-soft p-4">
            <div class="grid gap-3 text-sm md:grid-cols-3">
                <div>
                    <div class="font-semibold text-slate-950">像 Salesforce Pipeline</div>
                    <p class="mt-1 text-slate-600">每張卡是一個可成交的 OP，不是 Lead。Lead 合資格後才應轉換到這裡。</p>
                </div>
                <div>
                    <div class="font-semibold text-slate-950">階段等於下一步</div>
                    <p class="mt-1 text-slate-600">請保持金額、方案、預計成交日期準確，Dashboard 會用這些資料計 Pipeline。</p>
                </div>
                <div>
                    <div class="font-semibold text-slate-950">成交後才計佣</div>
                    <p class="mt-1 text-slate-600">Done Deal 會建立 Deal；Deal 付款狀態是 Paid 後才進入佣金計算。</p>
                </div>
            </div>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach($stages as $stage)
                @php
                    $stageOpportunities = $opportunities->get($stage, collect());
                    $stageTotal = $stageOpportunities->sum('estimated_deal_amount');
                    $columnClass = match($stage) {
                        'Done Deal' => 'border-emerald-200 bg-emerald-50/70',
                        'Lost' => 'border-rose-200 bg-rose-50/70',
                        'Negotiation' => 'border-amber-200 bg-amber-50/70',
                        default => 'border-teal-100 bg-teal-50/50',
                    };
                @endphp
                <div class="min-w-80 rounded-xl border p-3 {{ $columnClass }}">
                    <div class="mb-3">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-semibold text-slate-950">{{ $stageLabels[$stage] ?? $stage }}</h2>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $stageOpportunities->count() }}</span>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">{{ $stageHelp[$stage] ?? '' }}</div>
                        <div class="mt-2 text-sm font-semibold text-slate-800">HK${{ number_format((float) $stageTotal) }}</div>
                    </div>
                    <div class="kanban-column min-h-40 space-y-3" data-stage="{{ $stage }}">
                        @foreach($stageOpportunities as $opportunity)
                            @php
                                $isOverdue = $opportunity->expected_close_date && $opportunity->expected_close_date->isPast() && ! in_array($stage, ['Done Deal', 'Lost'], true);
                            @endphp
                            <div class="kanban-card cursor-grab rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md" data-id="{{ $opportunity->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a class="font-semibold text-slate-950 hover:text-teal-700" href="{{ route('crm.show', ['opportunities', $opportunity->id]) }}" wire:navigate>{{ $opportunity->opportunity_name }}</a>
                                        <div class="mt-1 truncate text-sm text-slate-500">{{ $opportunity->account?->company_name }}</div>
                                    </div>
                                    @if($isOverdue)
                                        <span class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">逾期</span>
                                    @endif
                                </div>
                                <div class="mt-3 grid gap-2 text-xs text-slate-600">
                                    <div class="flex items-center justify-between gap-3">
                                        <span>方案</span>
                                        <span class="font-semibold text-slate-950">{{ $opportunity->salesPlan?->plan_name ?? '未選擇' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>金額</span>
                                        <span class="font-semibold text-slate-950">HK${{ number_format((float) $opportunity->estimated_deal_amount) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>機率</span>
                                        <span class="font-semibold text-slate-950">{{ $opportunity->probability }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>預計成交</span>
                                        <span class="font-semibold {{ $isOverdue ? 'text-rose-700' : 'text-slate-950' }}">{{ optional($opportunity->expected_close_date)->toDateString() ?: '未設定' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>負責</span>
                                        <span class="font-semibold text-slate-950">{{ $opportunity->assignedSales?->name }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function initializeOpportunityKanban() {
            if (!window.Sortable) {
                window.setTimeout(initializeOpportunityKanban, 50);
                return;
            }

            document.querySelectorAll('.kanban-column').forEach((column) => {
                if (column.dataset.sortableReady === '1') {
                    return;
                }

                column.dataset.sortableReady = '1';
                new window.Sortable(column, {
                group: 'opportunities',
                animation: 150,
                ghostClass: 'opacity-50',
                onAdd: async (event) => {
                    const card = event.item;
                    const stage = event.to.dataset.stage;
                    const payload = { stage };

                    if (stage === 'Lost') {
                        const reason = prompt('請輸入流失原因');
                        if (!reason) { window.location.reload(); return; }
                        payload.lost_reason = reason;
                    }

                    if (stage === 'Done Deal') {
                        alert('成交金額會自動使用 OP 套餐固定售價，並建立 New Deal。請只確認付款及成交資料。');
                        payload.payment_status = prompt('付款狀態：Pending, Paid, Cancelled, Refunded', 'Pending') || 'Pending';
                        payload.payment_date = prompt('付款日期 YYYY-MM-DD，未付款可留空', '') || null;
                        payload.contract_date = prompt('合約日期 YYYY-MM-DD，未簽可留空', '') || null;
                    }

                    const response = await fetch(`{{ url('/kanban') }}/${card.dataset.id}/move`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        alert('更新階段失敗，請檢查必填資料。');
                        window.location.reload();
                    }
                },
            });
        });
        }

        if (document.readyState === 'complete') {
            initializeOpportunityKanban();
        } else {
            window.addEventListener('load', initializeOpportunityKanban);
            document.addEventListener('livewire:navigated', initializeOpportunityKanban);
        }
    </script>
</x-layouts::app>
