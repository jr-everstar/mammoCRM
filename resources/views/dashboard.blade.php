<x-layouts::app :title="__('儀表板')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="mc-panel-soft p-6">
            <div class="max-w-4xl">
                <div class="text-sm font-semibold uppercase tracking-wide text-teal-700 dark:text-teal-300">mammo care HK Sales CRM</div>
                <flux:heading size="xl">銷售管理儀表板</flux:heading>
                <flux:text class="mt-2">
                    {{ auth()->user()->isAdmin() ? '你現在看到的是全公司銷售、成交及佣金概況。' : (auth()->user()->canManageCrm() ? '你現在看到的是全公司商機 Leads、機會 OP，以及自己負責的成交及佣金概況。' : '你現在看到的是自己負責的商機 Leads、機會 OP、成交及佣金概況。') }}
                </flux:text>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-5">
            @foreach ([
                '商機 (Leads)' => $leadCount,
                '機會 (OP)' => $opportunityCount,
                '已成交' => $dealCount,
                '本月銷售額' => 'HK$'.number_format($monthlySales),
                'Pipeline 預計金額' => 'HK$'.number_format($pipelineValue),
            ] as $label => $value)
                <div class="mc-stat">
                    <div class="text-sm text-slate-500">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="mc-panel p-5 xl:col-span-2">
                <flux:heading>日常使用流程</flux:heading>
                <div class="mt-5 grid gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-teal-100 bg-teal-50 p-4 dark:border-teal-900 dark:bg-teal-950/30">
                        <div class="text-xs font-semibold uppercase text-teal-700 dark:text-teal-300">Step 1</div>
                        <div class="mt-1 font-semibold">先建立商機 Lead</div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">未確認公司資料、仍在初步聯絡、未有公司註冊號碼時，先放在「商機 (Leads)」。</p>
                    </div>
                    <div class="rounded-xl border border-sky-100 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                        <div class="text-xs font-semibold uppercase text-sky-700 dark:text-sky-300">Step 2</div>
                        <div class="mt-1 font-semibold">合資格後轉換</div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">當客戶有明確興趣並確認公司註冊號碼，按「轉換」建立 / 連結客戶帳戶及機會 OP。</p>
                    </div>
                    <div class="rounded-xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                        <div class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-300">Step 3</div>
                        <div class="mt-1 font-semibold">用看板跟進機會</div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">機會 OP 代表「某客戶的一單生意」。在看板由 Lead-in 推進至 Proposal、Negotiation。</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                        <div class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">Step 4</div>
                        <div class="mt-1 font-semibold">成交後計佣</div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">機會移到「Done Deal」會產生成交記錄；付款狀態為 Paid 後才納入佣金計算。</p>
                    </div>
                </div>
            </div>

            <div class="mc-panel p-5">
                <flux:heading>什麼時候建立 Account？</flux:heading>
                <div class="mt-4 space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">一般情況：不要一開始就建立 Account</div>
                        <p class="mt-1">如果只是查詢、WhatsApp、轉介、展會名片，請先建立「商機 (Leads)」。</p>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">建議做法：Lead → Convert</div>
                        <p class="mt-1">Lead 確認有機會成交，並取得公司註冊號碼後，才轉換成「客戶帳戶 + 機會 OP」。</p>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">直接建立 Account 的情況</div>
                        <p class="mt-1">只適用於已確認公司資料的現有客戶、已知商戶、Admin 匯入或已簽約客戶。</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="mc-panel p-5">
                <flux:heading>快速操作</flux:heading>
                <div class="mt-4 flex flex-wrap gap-3">
                    <flux:button :href="route('crm.create', 'leads')" wire:navigate>新增商機 Lead</flux:button>
                    <flux:button :href="route('crm.create', 'accounts')" wire:navigate>新增客戶 / 商戶</flux:button>
                    <flux:button :href="route('kanban')" wire:navigate>開啟銷售看板</flux:button>
                </div>
            </div>
            <div class="mc-panel p-5">
                <flux:heading>佣金提醒</flux:heading>
                <flux:text class="mt-2">只有付款狀態為 Paid 的成交才會納入每月佣金計算。Admin 可執行計算、審批及輸入調整原因。</flux:text>
                <flux:button class="mt-4" :href="route('commissions.index')" wire:navigate>查看佣金</flux:button>
            </div>
        </div>
    </div>
</x-layouts::app>
