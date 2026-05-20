@php
    $sections = [
        'sales-plans' => ['label' => '銷售方案 / Tier / HPA', 'icon' => 'clipboard-document-list', 'hint' => '管理方案售價、新成交佣金、Monthly Tier 及 HPA eligibility。'],
        'cost-configs' => ['label' => '成本設定', 'icon' => 'calculator', 'hint' => '佣金及毛利計算使用的成本假設。'],
        'commission-rules' => ['label' => '佣金規則', 'icon' => 'banknotes', 'hint' => '設定 Lead-in、Trial 及各方案新成交佣金。'],
        'opportunity-stage-rules' => ['label' => 'OP 階段機率', 'icon' => 'adjustments-horizontal', 'hint' => '設定每個 OP 階段自動帶出的成功機率。'],
        'monthly-tiers' => ['label' => '每月階梯獎金', 'icon' => 'chart-bar', 'hint' => '按每月合資格已付款銷售額計算累積獎金。'],
        'high-accelerators' => ['label' => '高階方案加速獎金', 'icon' => 'rocket-launch', 'hint' => '同月多張 HPA eligible 新成交，只發放最高符合獎金。'],
        'renewal-upgrade-rules' => ['label' => '續約 / 升級計算規則', 'icon' => 'arrow-path-rounded-square', 'hint' => '按交易類型設定佣金率及計入每月階梯比例。'],
        'trial-settings' => ['label' => '試用協議設定', 'icon' => 'document-text', 'hint' => '設定試用協議的 EverStar 地址、退回地址、費用及簽署預設值。'],
    ];

    $money = fn ($value) => 'HK$'.number_format((float) $value, 2);
    $integer = fn ($value) => number_format((float) $value, 0);
    $percent = fn ($value) => number_format((float) $value, 2).'%';
@endphp

<x-layouts::app :title="$title">
    <div class="space-y-6">
        <div class="mc-panel-soft overflow-hidden p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold uppercase tracking-wide text-teal-700 dark:text-teal-300">系統設定</div>
                    <flux:heading size="xl">{{ $sections[$type]['label'] ?? $title }}</flux:heading>
                    <flux:text>{{ $sections[$type]['hint'] ?? '管理 CRM 系統設定。' }}</flux:text>
                </div>
                <div class="rounded-full bg-white/80 px-4 py-2 text-sm font-semibold text-teal-700 shadow-sm ring-1 ring-teal-100 dark:bg-slate-900 dark:text-teal-200 dark:ring-teal-900">
                    {{ $records->total() }} 筆記錄
                </div>
            </div>
        </div>

        <div class="grid gap-2 md:grid-cols-3 xl:grid-cols-6">
            @foreach($sections as $nav => $meta)
                <a href="{{ route('config.index', $nav) }}" wire:navigate
                    class="rounded-xl border p-4 text-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $type === $nav ? 'border-teal-300 bg-teal-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-teal-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200' }}">
                    <div class="font-semibold">{{ $meta['label'] }}</div>
                    <div class="{{ $type === $nav ? 'text-teal-50' : 'text-slate-500 dark:text-slate-400' }} mt-1 line-clamp-2 text-xs">{{ $meta['hint'] }}</div>
                </a>
            @endforeach
        </div>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        @if($type === 'sales-plans')
            <div class="mc-panel-soft p-5">
                <div class="grid gap-4 text-sm md:grid-cols-2">
                    <div>
                        <div class="font-semibold text-slate-950">Monthly Tier 是否計入及是否觸發，由每個 Plan 決定。</div>
                        <p class="mt-1 text-slate-600">例如 PLAN A 可以 100% 計入 qualified amount，但不可觸發 tier bonus；PLAN B 或以上可以觸發。</p>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-950">HPA 是否計入，由每個 Plan 的「計入 HPA」決定。</div>
                        <p class="mt-1 text-slate-600">高階方案加速獎金會計算同月 paid new deal 中有幾多張 HPA eligible deal，不再靠 plan code 或 C/D level 猜規則。</p>
                    </div>
                </div>
            </div>

            <div class="mc-panel-soft p-4 text-sm text-slate-600">
                下方只顯示每個 plan 的關鍵資訊；需要修改售價、佣金、Tier 或 HPA 時，按該 plan 展開編輯即可。
            </div>

            <details class="mc-panel-soft overflow-hidden">
                <summary class="cursor-pointer list-none p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <flux:heading>新增銷售方案</flux:heading>
                            <flux:text>Plan Code 會由系統自動產生；展開後填寫固定售價、佣金及 Tier / HPA 設定。</flux:text>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-teal-700 shadow-sm ring-1 ring-teal-100">展開</span>
                    </div>
                </summary>
                <form method="POST" action="{{ route('config.store', $type) }}" class="border-t border-slate-100 p-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-4">
                        <flux:input name="plan_name" label="方案名稱" placeholder="PLAN E" required />
                        <flux:input name="display_order" type="number" label="顯示次序" value="{{ $records->total() + 1 }}" required />
                        <div>
                            <label class="mb-2 block text-sm font-medium">售價</label>
                            <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="selling_price" type="number" step="0.01" value="0" required></div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium">新成交佣金</label>
                            <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="new_deal_commission" type="number" step="0.01" value="0" required></div>
                        </div>
                        <flux:input name="report_commitment" type="number" label="12 個月報告承諾" value="0" required />
                        <flux:input name="average_cost_per_report" type="number" step="0.01" label="平均每份報告成本" value="0" required />
                        <flux:input name="included_ipad_quantity" type="number" label="包含硬件 - iPad" value="0" required />
                        <flux:input name="included_sensor_set_quantity" type="number" label="包含硬件 - sets of Sensors" value="0" required />
                        <div>
                            <label class="mb-2 block text-sm font-medium">Monthly Tier Count-in</label>
                            <div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3" name="monthly_tier_count_percentage" type="number" step="0.01" value="100" required><span class="mc-unit-suffix">%</span></div>
                        </div>
                        <div class="md:col-span-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-3">
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="checkbox" name="can_trigger_monthly_tier" value="1"> 可觸發 Monthly Tier</label>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="checkbox" name="hpa_eligible" value="1"> 計入 HPA</label>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> 啟用</label>
                        </div>
                    </div>
                    <div class="mt-4"><flux:button type="submit">新增方案及佣金</flux:button></div>
                </form>
            </details>

            <div class="space-y-3">
                @foreach($records as $plan)
                    <details class="mc-panel overflow-hidden">
                        <summary class="cursor-pointer list-none p-5">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-teal-700 dark:text-teal-300">{{ $plan->plan_code }}</div>
                                    <div class="text-lg font-semibold text-slate-950 dark:text-white">{{ $plan->plan_name }}</div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $money($plan->selling_price) }}</span>
                                    <span class="rounded-full {{ $plan->can_trigger_monthly_tier ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-50 text-slate-500 ring-slate-100' }} px-3 py-1 font-semibold ring-1">Tier {{ $plan->can_trigger_monthly_tier ? '可觸發' : '只計入' }}</span>
                                    <span class="rounded-full {{ $plan->hpa_eligible ? 'bg-teal-50 text-teal-700 ring-teal-100' : 'bg-slate-50 text-slate-500 ring-slate-100' }} px-3 py-1 font-semibold ring-1">HPA {{ $plan->hpa_eligible ? '計入' : '不計入' }}</span>
                                    <span class="rounded-full bg-white px-3 py-1 font-semibold text-teal-700 shadow-sm ring-1 ring-teal-100">編輯</span>
                                </div>
                            </div>
                        </summary>
                        <form method="POST" action="{{ route('config.update', [$type, $plan->id]) }}" class="border-t border-slate-100 dark:border-slate-800">
                            @csrf @method('PUT')
                            <div class="bg-gradient-to-r from-teal-50 to-white p-5 dark:from-teal-950/30 dark:to-slate-900">
                                <div class="text-xs font-semibold uppercase tracking-wide text-teal-700 dark:text-teal-300">{{ $plan->plan_code }}</div>
                                <div class="text-xl font-semibold">{{ $plan->plan_name }}</div>
                            </div>
                            <div class="grid gap-4 p-5 md:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="text-xs font-medium text-slate-500">System Plan Code</div>
                                <div class="mt-1 font-semibold text-slate-950 dark:text-white">{{ $plan->plan_code }}</div>
                            </div>
                            <flux:input name="plan_name" label="方案名稱" value="{{ $plan->plan_name }}" />
                            <div>
                                <label class="mb-2 block text-sm font-medium">售價</label>
                                <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="selling_price" type="number" step="0.01" value="{{ $plan->selling_price }}"></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">新成交佣金</label>
                                <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="new_deal_commission" type="number" step="0.01" value="{{ $planCommissions[$plan->id] ?? 0 }}"></div>
                                <div class="mt-1 text-xs text-slate-500">此欄會自動更新對應的 done deal commission rule。</div>
                            </div>
                            <flux:input name="display_order" type="number" label="顯示次序" value="{{ $plan->display_order }}" />
                            <div>
                                <label class="mb-2 block text-sm font-medium">平均每份報告成本</label>
                                <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="average_cost_per_report" type="number" step="0.01" value="{{ $plan->average_cost_per_report }}"></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">12 個月報告承諾</label>
                                <div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="report_commitment" type="number" value="{{ $plan->report_commitment }}"><span class="mc-unit-suffix">份報告</span></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">包含硬件 - iPad</label>
                                <div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="included_ipad_quantity" type="number" value="{{ $plan->included_ipad_quantity }}"><span class="mc-unit-suffix">iPad</span></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">包含硬件 - sets of Sensors</label>
                                <div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="included_sensor_set_quantity" type="number" value="{{ $plan->included_sensor_set_quantity }}"><span class="mc-unit-suffix">sets</span></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium">Monthly Tier Count-in</label>
                                <div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="monthly_tier_count_percentage" type="number" step="0.01" value="{{ $plan->monthly_tier_count_percentage }}"><span class="mc-unit-suffix">%</span></div>
                            </div>
                            <div class="md:col-span-2 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-3 dark:border-slate-800">
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                    <input type="checkbox" name="can_trigger_monthly_tier" value="1" @checked($plan->can_trigger_monthly_tier)>
                                    可觸發 Monthly Tier
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                    <input type="checkbox" name="hpa_eligible" value="1" @checked($plan->hpa_eligible)>
                                    計入 HPA
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($plan->is_active)>
                                    啟用
                                </label>
                            </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm dark:border-slate-800">
                                <div class="text-slate-500">現時售價： <span class="font-semibold text-slate-900 dark:text-white">{{ $money($plan->selling_price) }}</span> · 新成交佣金： <span class="font-semibold text-slate-900 dark:text-white">{{ $money($planCommissions[$plan->id] ?? 0) }}</span></div>
                                <flux:button type="submit">儲存方案</flux:button>
                            </div>
                        </form>
                    </details>
                @endforeach
            </div>
        @elseif($type === 'trial-settings')
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($records as $setting)
                    <form method="POST" action="{{ route('config.update', [$type, $setting->id]) }}" class="mc-panel p-5">
                        @csrf @method('PUT')
                        <div class="mb-4">
                            <div class="text-xs font-semibold uppercase text-teal-700">{{ str($setting->key)->replace('_', ' ')->title() }}</div>
                            <flux:heading>{{ $setting->name }}</flux:heading>
                            @if($setting->description)
                                <flux:text>{{ $setting->description }}</flux:text>
                            @endif
                        </div>
                        <flux:input name="name" label="Name" value="{{ $setting->name }}" />
                        <flux:textarea class="mt-4" name="value" label="Value">{{ $setting->value }}</flux:textarea>
                        <flux:textarea class="mt-4" name="description" label="Description">{{ $setting->description }}</flux:textarea>
                        <div class="mt-5"><flux:button type="submit">儲存設定</flux:button></div>
                    </form>
                @endforeach
            </div>
        @elseif($type === 'cost-configs')
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($records as $cost)
                    <form method="POST" action="{{ route('config.update', [$type, $cost->id]) }}" class="mc-panel p-5">
                        @csrf @method('PUT')
                        <div class="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase text-teal-700">{{ str($cost->key)->replace('_', ' ')->title() }}</div>
                                <flux:heading>{{ $cost->name }}</flux:heading>
                            </div>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($cost->is_active)> 啟用</label>
                        </div>
                        <flux:input name="name" label="名稱" value="{{ $cost->name }}" />
                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium">數值</label>
                            <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="value" type="number" step="0.01" value="{{ $cost->value }}"><input class="w-32 rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="unit" value="{{ $cost->unit }}"></div>
                        </div>
                        <flux:textarea class="mt-4" name="description" label="說明">{{ $cost->description }}</flux:textarea>
                        <div class="mt-5"><flux:button type="submit">儲存成本</flux:button></div>
                    </form>
                @endforeach
            </div>
        @elseif($type === 'commission-rules')
            <div class="mc-panel-soft p-5 text-sm text-slate-700">
                <div class="font-semibold text-slate-950">提示</div>
                <p class="mt-1">每個 Sales Plan 的「新成交佣金」請在「銷售方案」頁設定。這裡只保留 Lead-in / Trial 等全局規則，避免 plan 與獎金關係混亂。</p>
            </div>
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach($records as $rule)
                    <form method="POST" action="{{ route('config.update', [$type, $rule->id]) }}" class="mc-panel p-5">
                        @csrf @method('PUT')
                        <div class="mb-5 flex items-start justify-between">
                            <div>
                                <div class="text-xs font-semibold uppercase text-teal-700">{{ str($rule->key)->replace('_', ' ')->title() }}</div>
                                <flux:heading>{{ $rule->name }}</flux:heading>
                            </div>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> 啟用</label>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <flux:input name="name" label="名稱" value="{{ $rule->name }}" />
                            <flux:select name="type" label="類型"><option value="amount" @selected($rule->type === 'amount')>HK$ Amount</option><option value="percentage" @selected($rule->type === 'percentage')>百分比</option></flux:select>
                            <div>
                                <label class="mb-2 block text-sm font-medium">數值</label>
                                <div class="flex">
                                    <span class="mc-money-prefix">{{ $rule->type === 'percentage' ? '%' : 'HK$' }}</span>
                                    <input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="value" type="number" step="0.01" value="{{ $rule->value }}">
                                </div>
                            </div>
                        </div>
                        <flux:input class="mt-4" name="description" label="說明" value="{{ $rule->description }}" />
                        <div class="mt-5"><flux:button type="submit">儲存規則</flux:button></div>
                    </form>
                @endforeach
            </div>
        @elseif($type === 'opportunity-stage-rules')
            <div class="space-y-3">
                @foreach($records as $rule)
                    <form method="POST" action="{{ route('config.update', [$type, $rule->id]) }}" class="mc-panel grid items-end gap-4 p-5 md:grid-cols-6">
                        @csrf @method('PUT')
                        <div>
                            <div class="text-xs font-semibold uppercase text-teal-700">{{ $rule->stage }}</div>
                            <div class="text-lg font-semibold">{{ $rule->label }}</div>
                        </div>
                        <flux:input name="label" label="中文名稱" value="{{ $rule->label }}" />
                        <div>
                            <label class="mb-2 block text-sm font-medium">成功機率</label>
                            <div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="probability" type="number" min="0" max="100" value="{{ $rule->probability }}"><span class="mc-unit-suffix">%</span></div>
                        </div>
                        <flux:input name="display_order" type="number" label="排序" value="{{ $rule->display_order }}" />
                        <flux:input name="guidance" label="使用提示" value="{{ $rule->guidance }}" />
                        <div class="flex items-center justify-between gap-3"><label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> 啟用</label><flux:button type="submit">儲存</flux:button></div>
                    </form>
                @endforeach
            </div>
        @elseif($type === 'monthly-tiers')
            <div class="space-y-3">
                @foreach($records as $tier)
                    <form method="POST" action="{{ route('config.update', [$type, $tier->id]) }}" class="mc-panel grid items-end gap-4 p-5 md:grid-cols-5">
                        @csrf @method('PUT')
                        <div>
                            <div class="text-xs font-semibold uppercase text-teal-700">Tier {{ $tier->tier }}</div>
                            <div class="text-lg font-semibold">{{ $money($tier->threshold_amount) }}</div>
                        </div>
                        <div><label class="mb-2 block text-sm font-medium">門檻金額</label><div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="threshold_amount" type="number" step="0.01" value="{{ $tier->threshold_amount }}"></div></div>
                        <div><label class="mb-2 block text-sm font-medium">階梯獎金</label><div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="tier_bonus" type="number" step="0.01" value="{{ $tier->tier_bonus }}"></div></div>
                        <div><label class="mb-2 block text-sm font-medium">累積獎金</label><div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="cumulative_bonus" type="number" step="0.01" value="{{ $tier->cumulative_bonus }}"></div></div>
                        <div class="flex items-center justify-between gap-3"><label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($tier->is_active)> 啟用</label><flux:button type="submit">儲存</flux:button></div>
                    </form>
                @endforeach
            </div>
        @elseif($type === 'high-accelerators')
            @php($hpaPlans = $salesPlans->where('hpa_eligible', true)->values())
            <div class="mc-panel-soft p-5 text-sm text-slate-700">
                <div class="font-semibold text-slate-950">HPA 計算方式</div>
                <p class="mt-1">HPA 是否計入由 Sales Plan 的「計入 HPA」決定；每條 rule 可逐個 opt-in plan 設定所需數量，全部達標後才會觸發，系統只發放最高符合 bonus。</p>
                <p class="mt-1 text-slate-500">目前 opt-in HPA plans：{{ $hpaPlans->pluck('plan_name')->join('、') ?: '暫時沒有' }}。例子：PLAN C = 1、PLAN D = 1，即同月 paid new deal 需要各一張。</p>
            </div>
            <form method="POST" action="{{ route('config.store', $type) }}" class="mc-panel-soft p-5">
                @csrf
                <div class="mb-4">
                    <flux:heading>新增 HPA 規則</flux:heading>
                    <flux:text>逐個設定 opt-in HPA plan 需要幾多張 paid new deal，以及對應 bonus。Rule Code 會由系統自動產生。</flux:text>
                </div>
                <div class="grid items-end gap-4 md:grid-cols-4">
                    <flux:input name="name" label="Rule 名稱" placeholder="PLAN C x1 + PLAN D x1" required />
                    @foreach($hpaPlans as $plan)
                        <flux:input name="hpa_plan_quantities[{{ $plan->id }}]" type="number" label="{{ $plan->plan_name }} QTY" value="0" min="0" />
                    @endforeach
                    <div>
                        <label class="mb-2 block text-sm font-medium">Bonus</label>
                        <div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="bonus" type="number" step="0.01" value="0" required></div>
                    </div>
                    <flux:input name="priority" type="number" label="優先次序" value="{{ $records->total() + 1 }}" required />
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> 啟用</label>
                </div>
                <div class="mt-4"><flux:button type="submit">新增 HPA 規則</flux:button></div>
            </form>
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($records as $rule)
                    <div class="mc-panel p-5">
                        <form method="POST" action="{{ route('config.update', [$type, $rule->id]) }}">
                            @csrf @method('PUT')
                            <div class="mb-4 flex items-center justify-between"><div><div class="text-xs font-semibold uppercase text-teal-700">{{ $rule->code }}</div><flux:heading>{{ $rule->name }}</flux:heading></div><label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> 啟用</label></div>
                            <flux:input name="name" label="Rule 名稱" value="{{ $rule->name }}" />
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach($hpaPlans as $plan)
                                    <flux:input name="hpa_plan_quantities[{{ $plan->id }}]" type="number" label="{{ $plan->plan_name }} QTY" value="{{ $rule->salesPlanRequirements->firstWhere('id', $plan->id)?->pivot->required_quantity ?? 0 }}" min="0" />
                                @endforeach
                                <flux:input name="priority" type="number" label="優先次序" value="{{ $rule->priority }}" />
                                <div><label class="mb-2 block text-sm font-medium">Bonus</label><div class="flex"><span class="mc-money-prefix">HK$</span><input class="w-full rounded-e-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="bonus" type="number" step="0.01" value="{{ $rule->bonus }}"></div></div>
                            </div>
                            <div class="mt-5"><flux:button type="submit">儲存加速規則</flux:button></div>
                        </form>
                        <form method="POST" action="{{ route('config.destroy', [$type, $rule->id]) }}" class="mt-3">
                            @csrf @method('DELETE')
                            <flux:button type="submit" variant="danger" onclick="return confirm('Delete this HPA rule?')">刪除規則</flux:button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($records as $rule)
                    <form method="POST" action="{{ route('config.update', [$type, $rule->id]) }}" class="mc-panel p-5">
                        @csrf @method('PUT')
                        <div class="mb-4 flex items-center justify-between"><div><div class="text-xs font-semibold uppercase text-teal-700">{{ str($rule->deal_type)->replace('_', ' ')->title() }}</div><flux:heading>{{ $rule->name }}</flux:heading></div><label class="text-sm"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> 啟用</label></div>
                        <flux:input name="name" label="名稱" value="{{ $rule->name }}" />
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div><label class="mb-2 block text-sm font-medium">佣金率</label><div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="commission_rate" type="number" step="0.01" value="{{ $rule->commission_rate }}"><span class="mc-unit-suffix">%</span></div></div>
                            <div><label class="mb-2 block text-sm font-medium">計入階梯比例</label><div class="flex"><input class="w-full rounded-s-lg border border-slate-200 px-3 dark:border-slate-700 dark:bg-slate-900" name="monthly_tier_count_percentage" type="number" step="0.01" value="{{ $rule->monthly_tier_count_percentage }}"><span class="mc-unit-suffix">%</span></div></div>
                        </div>
                        <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" name="can_trigger_monthly_tier" value="1" @checked($rule->can_trigger_monthly_tier)> 可獨立觸發每月階梯</label>
                        <flux:textarea class="mt-4" name="definition" label="定義">{{ $rule->definition }}</flux:textarea>
                        <div class="mt-5"><flux:button type="submit">儲存續約規則</flux:button></div>
                    </form>
                @endforeach
            </div>
        @endif

        {{ $records->links() }}
    </div>
</x-layouts::app>
