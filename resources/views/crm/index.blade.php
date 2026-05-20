<x-layouts::app :title="$title">
    @php
        $moduleLabels = [
            'accounts' => '客戶 / 商戶帳戶',
            'contacts' => '聯絡人 Contacts',
            'leads' => '商機 (Leads)',
            'opportunities' => '機會 (OP)',
            'deals' => '成交記錄',
        ];
        $columnLabels = [
            'company_name' => '公司名稱',
            'company_registration_number' => '公司註冊號碼',
            'business_type' => '業務類型',
            'status' => '狀態',
            'name' => '姓名',
            'title' => '職銜',
            'email' => '電郵',
            'phone' => '電話',
            'lead_name' => '商機名稱',
            'source' => '來源',
            'opportunity_name' => '機會名稱',
            'stage' => '階段',
            'estimated_deal_amount' => '預計成交金額',
            'expected_close_date' => '預計成交日期',
            'deal_type' => '成交類型',
            'deal_amount' => '成交金額',
            'payment_status' => '付款狀態',
            'payment_date' => '付款日期',
            'commission_status' => '佣金狀態',
        ];
        $leadStatusLabels = [
            'New' => '新商機',
            'Contacted' => '已聯絡',
            'Meeting Scheduled' => '已約會議',
            'Demo Completed' => '已完成示範',
            'Trial Arranged' => '已安排試用',
            'Trial Completed' => '已完成試用',
            'Converted' => '已轉換',
            'Lost' => '已流失',
        ];
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
            'Lead-in' => '已確認有潛在需求，可開始銷售跟進。',
            'Meeting / Demo' => '已安排會議、示範或需求確認。',
            'Trial' => '客戶正在或準備試用。',
            'Proposal' => '已提供方案與報價。',
            'Negotiation' => '正在處理價錢、條款或付款安排。',
            'Done Deal' => '已簽約，應建立成交記錄。',
            'Lost' => '不再跟進，必須記錄原因。',
        ];
        $moneyColumns = ['estimated_deal_amount', 'deal_amount'];
        $formatValue = fn ($column, $value) => in_array($column, $moneyColumns, true) && is_numeric($value)
            ? 'HK$'.number_format((float) $value, 2)
            : $value;
        $displayTitle = $moduleLabels[$module] ?? $title;
        $isSalesWorkspace = in_array($module, ['leads', 'opportunities'], true);
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ $displayTitle }}</flux:heading>
                <flux:text>
                    @if($module === 'leads')
                        像 Salesforce 一樣先收集商機 Leads、確認是否合資格，再轉換成客戶帳戶及機會 OP。
                    @elseif($module === 'opportunities')
                        用銷售階段管理 Pipeline。真正要推進成交時，請切到看板拖拉階段。
                    @else
                        搜尋、查看及管理{{ $displayTitle }}。
                    @endif
                </flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($module === 'opportunities')
                    <flux:button variant="ghost" :href="route('kanban')" wire:navigate>打開看板</flux:button>
                @endif
                @if($module !== 'deals' || auth()->user()->isAdmin())
                    <flux:button :href="route('crm.create', $module)" wire:navigate>新增</flux:button>
                @endif
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if($isSalesWorkspace)
            <div class="grid gap-3 md:grid-cols-4">
                @if($module === 'leads')
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">全部商機</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-950">{{ number_format($indexStats['total'] ?? 0) }}</div>
                    </div>
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">新商機</div>
                        <div class="mt-1 text-2xl font-semibold text-sky-700">{{ number_format($indexStats['new'] ?? 0) }}</div>
                    </div>
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">跟進中</div>
                        <div class="mt-1 text-2xl font-semibold text-teal-700">{{ number_format($indexStats['active'] ?? 0) }}</div>
                    </div>
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">已轉換</div>
                        <div class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($indexStats['converted'] ?? 0) }}</div>
                    </div>
                @else
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">機會 OP</div>
                        <div class="mt-1 text-2xl font-semibold text-slate-950">{{ number_format($indexStats['total'] ?? 0) }}</div>
                    </div>
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">Pipeline 金額</div>
                        <div class="mt-1 text-2xl font-semibold text-teal-700">HK${{ number_format($indexStats['pipeline'] ?? 0) }}</div>
                    </div>
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">本月預計成交</div>
                        <div class="mt-1 text-2xl font-semibold text-sky-700">HK${{ number_format($indexStats['closing_month'] ?? 0) }}</div>
                    </div>
                    <div class="mc-stat">
                        <div class="text-xs font-medium text-slate-500">已成交機會</div>
                        <div class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($indexStats['won'] ?? 0) }}</div>
                    </div>
                @endif
            </div>

            <div class="mc-panel-soft p-4">
                @if($module === 'leads')
                    <div class="grid gap-3 text-sm md:grid-cols-3">
                        <div>
                            <div class="font-semibold text-slate-900">1. 先建立商機 Lead</div>
                            <p class="mt-1 text-slate-600">未確認公司註冊號碼、預算或方案前，先放在 Leads。</p>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900">2. 跟進到合資格</div>
                            <p class="mt-1 text-slate-600">聯絡、會議、示範、試用都在商機狀態更新。</p>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900">3. Convert</div>
                            <p class="mt-1 text-slate-600">確定公司資料及有實際成交機會後，轉換成 Account + OP。</p>
                        </div>
                    </div>
                @else
                    <div class="grid gap-3 text-sm md:grid-cols-3">
                        <div>
                            <div class="font-semibold text-slate-900">Pipeline 是銷售中的 Deal</div>
                            <p class="mt-1 text-slate-600">每張卡代表一個可能成交的方案與金額。</p>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900">階段代表下一步</div>
                            <p class="mt-1 text-slate-600">從示範、試用、報價到洽談，請保持最新。</p>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900">成交請走 Done Deal</div>
                            <p class="mt-1 text-slate-600">移到 Done Deal 後會建立成交記錄，再用付款狀態計佣。</p>
                        </div>
                    </div>
                @endif
            </div>

            <form class="mc-panel flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-64 flex-1">
                    <flux:input name="search" value="{{ request('search') }}" placeholder="搜尋名稱、公司、聯絡人" label="搜尋" />
                </div>
                @if($module === 'leads')
                    <flux:select name="status" label="商機狀態">
                        <option value="">全部狀態</option>
                        @foreach($leadStatuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $leadStatusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:select name="stage" label="銷售階段">
                        <option value="">全部階段</option>
                        @foreach($stages as $stage)
                            <option value="{{ $stage }}" @selected(request('stage') === $stage)>{{ $stageLabels[$stage] ?? $stage }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select name="close" label="成交日期">
                        <option value="">全部日期</option>
                        <option value="this_month" @selected(request('close') === 'this_month')>本月預計成交</option>
                        <option value="overdue" @selected(request('close') === 'overdue')>逾期未成交</option>
                    </flux:select>
                @endif
                <flux:button type="submit">套用</flux:button>
                <flux:button variant="ghost" :href="route('crm.index', $module)" wire:navigate>清除</flux:button>
            </form>

            @if($module === 'opportunities')
                <div class="mc-panel overflow-x-auto p-4">
                    <div class="flex min-w-[720px] items-start gap-2">
                        @foreach($stages as $stage)
                            <a href="{{ route('crm.index', ['module' => 'opportunities', 'stage' => $stage]) }}" wire:navigate class="flex-1 rounded-lg border px-3 py-2 text-sm transition {{ request('stage') === $stage ? 'border-teal-300 bg-teal-50 text-teal-900' : 'border-slate-200 bg-white text-slate-600 hover:border-teal-200 hover:bg-teal-50/50' }}">
                                <div class="font-semibold">{{ $stageLabels[$stage] ?? $stage }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $stageHelp[$stage] ?? '' }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-3">
                @forelse($records as $record)
                    @if($module === 'leads')
                        @php
                            $status = $record->status;
                            $statusClass = match($status) {
                                'Converted' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'Lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                'New' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                default => 'bg-teal-50 text-teal-700 ring-teal-200',
                            };
                        @endphp
                        <div class="mc-panel p-4 transition hover:border-teal-200 hover:shadow-md">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('crm.show', ['leads', $record->id]) }}" wire:navigate class="text-lg font-semibold text-slate-950 hover:text-teal-700">{{ $record->lead_name }}</a>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">{{ $leadStatusLabels[$status] ?? $status }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $record->company_name ?: '公司未確認' }} · {{ $record->business_type ?: '業務類型未填' }}</div>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span class="rounded bg-slate-100 px-2 py-1">來源：{{ $record->source ?: '未填' }}</span>
                                        <span class="rounded bg-slate-100 px-2 py-1">聯絡人：{{ $record->contact_person ?: '未填' }}</span>
                                        <span class="rounded bg-slate-100 px-2 py-1">負責：{{ $record->assignedSales?->name ?: '-' }}</span>
                                        <span class="rounded bg-slate-100 px-2 py-1">更新：{{ $record->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <flux:button size="sm" variant="ghost" :href="route('crm.edit', ['leads', $record->id])" wire:navigate>更新狀態</flux:button>
                                    <flux:button size="sm" :href="route('crm.show', ['leads', $record->id])" wire:navigate>{{ $record->status === 'Converted' ? '查看' : '轉換 / 查看' }}</flux:button>
                                </div>
                            </div>
                        </div>
                    @else
                        @php
                            $stage = $record->stage;
                            $stageClass = match($stage) {
                                'Done Deal' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'Lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                'Negotiation' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                default => 'bg-teal-50 text-teal-700 ring-teal-200',
                            };
                            $isOverdue = $record->expected_close_date && $record->expected_close_date->isPast() && ! in_array($stage, ['Done Deal', 'Lost'], true);
                        @endphp
                        <div class="mc-panel p-4 transition hover:border-teal-200 hover:shadow-md">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('crm.show', ['opportunities', $record->id]) }}" wire:navigate class="text-lg font-semibold text-slate-950 hover:text-teal-700">{{ $record->opportunity_name }}</a>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $stageClass }}">{{ $stageLabels[$stage] ?? $stage }}</span>
                                        @if($isOverdue)
                                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">逾期</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-sm text-slate-600">{{ $record->account?->company_name }} · {{ $record->salesPlan?->plan_name ?? '未選方案' }}</div>
                                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                                        <div>
                                            <div class="text-xs text-slate-500">預計金額</div>
                                            <div class="font-semibold text-slate-950">HK${{ number_format((float) $record->estimated_deal_amount, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-slate-500">成功機率</div>
                                            <div class="font-semibold text-slate-950">{{ $record->probability }}%</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-slate-500">預計成交</div>
                                            <div class="font-semibold {{ $isOverdue ? 'text-rose-700' : 'text-slate-950' }}">{{ optional($record->expected_close_date)->format('Y-m-d') ?: '未設定' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-slate-500">負責銷售</div>
                                            <div class="font-semibold text-slate-950">{{ $record->assignedSales?->name ?: '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <flux:button size="sm" variant="ghost" :href="route('crm.edit', ['opportunities', $record->id])" wire:navigate>編輯</flux:button>
                                    <flux:button size="sm" :href="route('crm.show', ['opportunities', $record->id])" wire:navigate>查看詳情</flux:button>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="mc-panel p-8 text-center text-sm text-slate-500">暫時沒有記錄。可先新增，或清除篩選條件。</div>
                @endforelse
            </div>

            {{ $records->links() }}
        @else
            <form class="flex gap-2">
                <flux:input name="search" value="{{ request('search') }}" placeholder="搜尋{{ $displayTitle }}" />
                <flux:button type="submit">篩選</flux:button>
            </form>

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                        <tr>
                            @foreach($columns as $column)
                                <th class="px-4 py-3">{{ $columnLabels[$column] ?? str($column)->replace('_', ' ')->title() }}</th>
                            @endforeach
                            <th class="px-4 py-3">負責人</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($records as $record)
                            <tr>
                                @foreach($columns as $column)
                                    <td class="px-4 py-3">{{ $formatValue($column, data_get($record, $column)) }}</td>
                                @endforeach
                                <td class="px-4 py-3">
                                    {{ $record->account?->company_name ?? $record->manager?->name ?? $record->assignedSales?->name ?? $record->salesUser?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <flux:button size="sm" variant="ghost" :href="route('crm.show', [$module, $record->id])" wire:navigate>查看</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($columns) + 2 }}" class="px-4 py-8 text-center text-zinc-500">暫時沒有記錄。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $records->links() }}
        @endif
    </div>
</x-layouts::app>
