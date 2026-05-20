<x-layouts::app :title="$title">
    @php
        $moduleLabels = [
            'accounts' => '客戶 / 商戶帳戶',
            'contacts' => '聯絡人 Contacts',
            'leads' => '商機 (Leads)',
            'opportunities' => '機會 (OP)',
            'deals' => '成交記錄',
        ];
        $fieldLabels = [
            'company_name' => '公司名稱',
            'company_registration_number' => '公司註冊號碼',
            'contact_person_name' => '聯絡人姓名',
            'contact_person_title' => '聯絡人職銜',
            'contact_phone' => '聯絡電話',
            'contact_email' => '聯絡電郵',
            'business_type' => '業務類型',
            'address' => '地址',
            'account_manager_id' => '客戶經理',
            'created_by' => '建立者',
            'status' => '狀態',
            'notes' => '備註',
            'name' => '姓名',
            'title' => '職銜',
            'email' => '電郵',
            'phone' => '電話',
            'whatsapp' => 'WhatsApp',
            'is_primary' => '主要聯絡人',
            'lead_name' => '商機名稱',
            'source' => '來源',
            'assigned_sales_id' => '負責銷售',
            'opportunity_name' => '機會名稱',
            'estimated_deal_amount' => '預計成交金額',
            'expected_close_date' => '預計成交日期',
            'stage' => '階段',
            'lost_reason' => '流失原因',
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
        $moneyFields = ['estimated_deal_amount', 'deal_amount', 'selling_price', 'value'];
        $formatDetail = function ($key, $value) use ($moneyFields) {
            if (in_array($key, $moneyFields, true) && is_numeric($value)) {
                return 'HK$'.number_format((float) $value, 2);
            }

            return is_scalar($value) ? $value : json_encode($value);
        };
        $displayTitle = $moduleLabels[$module] ?? $title;
        $isAccount = $module === 'accounts';
        $isLead = $module === 'leads';
        $isOpportunity = $module === 'opportunities';
    @endphp

    <div class="space-y-5">
        @if($isAccount)
            <div class="mc-panel-soft p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-200">{{ $record->status }}</span>
                            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Account</span>
                        </div>
                        <flux:heading size="xl">{{ $record->company_name }}</flux:heading>
                        <flux:text>{{ $record->business_type ?: '業務類型未填' }} · CR No. {{ $record->company_registration_number }}</flux:text>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="ghost" :href="route('crm.index', 'accounts')" wire:navigate>返回列表</flux:button>
                        <flux:button variant="ghost" :href="route('crm.create', ['module' => 'contacts', 'account_id' => $record->id])" wire:navigate>新增 Contact</flux:button>
                        <flux:button variant="ghost" :href="route('crm.create', ['module' => 'deals', 'account_id' => $record->id, 'deal_type' => 'am_managed_renewal'])" wire:navigate>新增續約 / 升級 Deal</flux:button>
                        <flux:button :href="route('crm.edit', ['accounts', $record->id])" wire:navigate>編輯 Account</flux:button>
                    </div>
                </div>
            </div>

            @if(session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-3 md:grid-cols-4">
                <div class="mc-stat">
                    <div class="text-xs font-medium text-slate-500">Contacts</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-950">{{ $accountRelated['contacts']->count() }}</div>
                </div>
                <div class="mc-stat">
                    <div class="text-xs font-medium text-slate-500">商機 (Leads)</div>
                    <div class="mt-1 text-2xl font-semibold text-sky-700">{{ $accountRelated['leads']->count() }}</div>
                </div>
                <div class="mc-stat">
                    <div class="text-xs font-medium text-slate-500">機會 (OP)</div>
                    <div class="mt-1 text-2xl font-semibold text-teal-700">{{ $accountRelated['opportunities']->count() }}</div>
                </div>
                <div class="mc-stat">
                    <div class="text-xs font-medium text-slate-500">已付款成交</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-700">HK${{ number_format((float) $accountRelated['deals']->where('payment_status', 'Paid')->sum('deal_amount')) }}</div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[360px_1fr]">
                <div class="space-y-4">
                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">Account 資料</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">客戶經理</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->manager?->name ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">主要聯絡資料</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->contact_person_name ?: '-' }} · {{ $record->contact_phone ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">地址</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->address ?: '未填' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">備註</dt>
                                <dd class="mt-1 whitespace-pre-line text-slate-950">{{ $record->notes ?: '未填' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mc-panel p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-slate-950">Contacts</h2>
                            <a class="text-sm font-medium text-teal-700" href="{{ route('crm.create', ['module' => 'contacts', 'account_id' => $record->id]) }}" wire:navigate>新增</a>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse($accountRelated['contacts'] as $contact)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                    <div class="flex items-start justify-between gap-2">
                                        <a href="{{ route('crm.show', ['contacts', $contact->id]) }}" wire:navigate class="font-semibold text-slate-950 hover:text-teal-700">{{ $contact->name }}</a>
                                        @if($contact->is_primary)
                                            <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700 ring-1 ring-teal-200">主要</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-slate-600">{{ $contact->title ?: '職銜未填' }}</div>
                                    <div class="mt-2 text-xs text-slate-500">{{ $contact->phone ?: '-' }} · {{ $contact->email ?: '-' }}</div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">未有 Contacts。Account 可以有多個聯絡人。</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">客戶歷史總覽</h2>
                        <p class="mt-1 text-sm text-slate-600">這裡集中顯示此客戶過去所有商機 Leads、機會 OP、報價、合約及發票。</p>
                    </div>

                    @foreach([
                        'leads' => ['title' => '商機 (Leads)', 'empty' => '沒有相關 Leads。'],
                        'opportunities' => ['title' => '機會 (OP)', 'empty' => '沒有相關 OP。'],
                        'deals' => ['title' => '成交 / Deals', 'empty' => '沒有成交記錄。'],
                        'quotations' => ['title' => 'Quotations 報價', 'empty' => '沒有報價記錄。'],
                        'contracts' => ['title' => 'Contracts 合約', 'empty' => '沒有合約記錄。'],
                        'trialAgreements' => ['title' => 'Trial Agreements 試用協議', 'empty' => '沒有試用協議。'],
                        'invoices' => ['title' => 'Invoices 發票', 'empty' => '沒有發票記錄。'],
                    ] as $key => $section)
                        <div class="mc-panel overflow-hidden">
                            <div class="border-b border-slate-200 px-5 py-3">
                                <h3 class="font-semibold text-slate-950">{{ $section['title'] }}</h3>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse($accountRelated[$key] as $item)
                                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                                        <div>
                                            @if($key === 'leads')
                                                <a href="{{ route('crm.show', ['leads', $item->id]) }}" wire:navigate class="font-semibold text-slate-950 hover:text-teal-700">{{ $item->lead_name }}</a>
                                                <div class="text-slate-500">{{ $item->status }} · {{ $item->source ?: '來源未填' }}</div>
                                            @elseif($key === 'opportunities')
                                                <a href="{{ route('crm.show', ['opportunities', $item->id]) }}" wire:navigate class="font-semibold text-slate-950 hover:text-teal-700">{{ $item->opportunity_name }}</a>
                                                <div class="text-slate-500">{{ $item->stage }} · {{ $item->salesPlan?->plan_name ?? '未選方案' }}</div>
                                            @elseif($key === 'deals')
                                                <a href="{{ route('crm.show', ['deals', $item->id]) }}" wire:navigate class="font-semibold text-slate-950 hover:text-teal-700">{{ $item->salesPlan?->plan_name ?? 'Deal' }}</a>
                                                <div class="text-slate-500">{{ $item->deal_type }} · {{ $item->payment_status }}</div>
                                            @else
                                                <div class="font-semibold text-slate-950">{{ $item->title }}</div>
                                                <div class="text-slate-500">{{ $item->document_number ?: '未有編號' }} · {{ $item->status }}</div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold text-slate-950">
                                                @if(in_array($key, ['opportunities'], true))
                                                    HK${{ number_format((float) $item->estimated_deal_amount, 2) }}
                                                @elseif($key === 'deals')
                                                    HK${{ number_format((float) $item->deal_amount, 2) }}
                                            @elseif(in_array($key, ['quotations', 'contracts', 'trialAgreements', 'invoices'], true))
                                                    HK${{ number_format((float) $item->amount, 2) }}
                                                @else
                                                    {{ $item->updated_at->format('Y-m-d') }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-500">{{ optional($item->document_date ?? $item->payment_date ?? $item->expected_close_date ?? null)->format('Y-m-d') ?: $item->created_at->format('Y-m-d') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-4 text-sm text-slate-500">{{ $section['empty'] }}</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($isLead)
            @php
                $name = $record->lead_name;
                $subtitle = ($record->company_name ?: '公司未確認').' · '.($record->business_type ?: '業務類型未填');
                $status = $record->status;
                $statusClass = match($status) {
                    'Converted' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'Lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
                    'New' => 'bg-sky-50 text-sky-700 ring-sky-200',
                    default => 'bg-teal-50 text-teal-700 ring-teal-200',
                };
                $nextStep = match($status) {
                    'New' => '先致電或 WhatsApp 聯絡，確認需求與決策人。',
                    'Contacted' => '安排會議、Demo 或初步需求訪談。',
                    'Meeting Scheduled' => '完成會議後更新為 Demo Completed 或安排 Trial。',
                    'Demo Completed' => '確認是否可安排 Trial，並補齊公司註冊號碼。',
                    'Trial Arranged' => '跟進試用安排與日期。',
                    'Trial Completed' => '如有購買意向，請轉換成 Account + OP。',
                    'Converted' => '已轉換，下一步請在 OP 跟進成交。',
                    'Lost' => '已流失，保留原因供日後分析。',
                    default => '更新商機狀態，保持下一步清楚。',
                };
            @endphp

            <div class="mc-panel-soft p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">{{ $leadStatusLabels[$status] ?? $status }}</span>
                            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Lead</span>
                        </div>
                        <flux:heading size="xl">{{ $name }}</flux:heading>
                        <flux:text>{{ $subtitle }}</flux:text>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="ghost" :href="route('crm.index', 'leads')" wire:navigate>返回列表</flux:button>
                        <flux:button :href="route('crm.edit', ['leads', $record->id])" wire:navigate>編輯商機</flux:button>
                    </div>
                </div>
            </div>

            @if(session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 lg:grid-cols-[1fr_380px]">
                <div class="space-y-4">
                    <div class="mc-panel p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">銷售指引</h2>
                                <p class="mt-1 text-sm text-slate-600">{{ $nextStep }}</p>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs text-slate-500">聯絡人</div>
                                <div class="mt-1 font-semibold text-slate-950">{{ $record->contact_person ?: '未填' }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs text-slate-500">電話</div>
                                <div class="mt-1 font-semibold text-slate-950">{{ $record->contact_phone ?: '未填' }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="text-xs text-slate-500">電郵</div>
                                <div class="mt-1 font-semibold text-slate-950">{{ $record->contact_email ?: '未填' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">商機 Lead 資料</h2>
                        <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">公司名稱</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->company_name ?: '未確認' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">公司註冊號碼</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->company_registration_number ?: '未確認' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">來源</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->source ?: '未填' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">負責銷售</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->assignedSales?->name ?: '-' }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs font-medium text-slate-500">備註</dt>
                                <dd class="mt-1 whitespace-pre-line text-slate-950">{{ $record->notes ?: '未填' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">Comment</h2>
                        <p class="mt-1 text-sm text-slate-600">Admin 與 Sales 溝通記錄會按時間保留。</p>
                        <form method="POST" action="{{ route('crm.comments.store', ['leads', $record->id]) }}" class="mt-4 space-y-3">
                            @csrf
                            <flux:textarea name="body" label="新增 Comment" required>{{ old('body') }}</flux:textarea>
                            <flux:button type="submit">新增 Comment</flux:button>
                        </form>
                        <div class="mt-5 space-y-4">
                            @forelse($record->comments as $comment)
                                <div class="border-l-2 border-slate-200 pl-3 text-sm">
                                    <div class="font-medium text-slate-950">{{ $comment->author?->name ?? '已刪除用戶' }}</div>
                                    <div class="mt-1 whitespace-pre-line text-slate-600">{{ $comment->body }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $comment->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">暫時沒有 Comment。</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <form method="POST" action="{{ route('crm.remarks', ['leads', $record->id]) }}" class="mc-panel p-5">
                        @csrf
                        <h2 class="text-base font-semibold text-slate-950">銷售備註 / Message</h2>
                        <p class="mt-1 text-sm text-slate-600">更新內部備註，例如客戶背景、跟進重點或下一步。</p>
                        <div class="mt-4 space-y-3">
                            <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
                            <flux:button type="submit" class="w-full">儲存備註</flux:button>
                        </div>
                    </form>

                    @if($record->status !== 'Converted')
                        <form method="POST" action="{{ route('leads.convert', $record) }}" class="mc-panel p-5">
                            @csrf
                            <h2 class="text-base font-semibold text-slate-950">轉換成商機</h2>
                            <p class="mt-1 text-sm text-slate-600">當公司資料已確認，並且有實際方案/金額可跟進，就轉換成 Account + OP。</p>
                            <div class="mt-4 space-y-4">
                                <flux:input name="company_registration_number" label="公司註冊號碼" value="{{ $record->company_registration_number }}" required />
                                <flux:input name="opportunity_name" label="機會 OP 名稱" value="{{ $record->company_name ?: $record->lead_name }} OP" required />
                                <flux:select name="sales_plan_id" label="套餐 / 方案" required>
                                    <option value="">請選擇方案</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->plan_name }} · HK${{ number_format((float) $plan->selling_price) }}</option>
                                    @endforeach
                                </flux:select>
                                <div class="rounded-lg border border-teal-100 bg-teal-50 p-3 text-sm text-teal-800">
                                    OP 金額會自動使用套餐固定售價，無須人手輸入金額。
                                </div>
                                <flux:input name="expected_close_date" type="date" label="預計成交日期" />
                                <flux:button type="submit" class="w-full">轉換成 OP</flux:button>
                            </div>
                        </form>
                    @else
                        <div class="mc-panel p-5">
                            <h2 class="text-base font-semibold text-slate-950">已轉換</h2>
                            <p class="mt-1 text-sm text-slate-600">這條商機已變成客戶與機會 OP，後續請到 OP 跟進。</p>
                            @if($record->converted_opportunity_id)
                                <flux:button class="mt-4" :href="route('crm.show', ['opportunities', $record->converted_opportunity_id])" wire:navigate>查看機會 OP</flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @elseif($isOpportunity)
            @php
                $stage = $record->stage;
                $stageIndex = array_search($stage, $stages, true);
                $nextPipelineStages = array_values(array_filter($stages, fn ($pipelineStage) => $pipelineStage !== 'Lost'));
                $nextStageIndex = array_search($stage, $nextPipelineStages, true);
                $nextStage = $nextStageIndex === false ? null : ($nextPipelineStages[$nextStageIndex + 1] ?? null);
                $isOverdue = $record->expected_close_date && $record->expected_close_date->isPast() && ! in_array($stage, ['Done Deal', 'Lost'], true);
                $stageClass = match($stage) {
                    'Done Deal' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'Lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
                    'Negotiation' => 'bg-amber-50 text-amber-700 ring-amber-200',
                    default => 'bg-teal-50 text-teal-700 ring-teal-200',
                };
                $nextStep = match($stage) {
                    'Lead-in' => '確認決策人、需求、預算及可能方案。',
                    'Meeting / Demo' => '完成 Demo 後，決定是否安排 Trial 或直接報價。',
                    'Trial' => '跟進試用結果，收集阻力及購買時間表。',
                    'Proposal' => '確認方案、金額、硬件安排及付款條件。',
                    'Negotiation' => '處理最後條款，準備簽約與付款。',
                    'Done Deal' => '檢查 Deal 付款狀態，付款後才會計算佣金。',
                    'Lost' => '檢查流失原因，日後做 conversion report 分析。',
                    default => '更新階段及下一步。',
                };
            @endphp

            <div class="mc-panel-soft p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $stageClass }}">{{ $stageLabels[$stage] ?? $stage }}</span>
                            @if($isOverdue)
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">逾期</span>
                            @endif
                            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">OP</span>
                        </div>
                        <flux:heading size="xl">{{ $record->opportunity_name }}</flux:heading>
                        <flux:text>{{ $record->account?->company_name }} · {{ $record->salesPlan?->plan_name ?? '未選方案' }}</flux:text>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="ghost" :href="route('kanban')" wire:navigate>看板</flux:button>
                        <flux:button :href="route('crm.edit', ['opportunities', $record->id])" wire:navigate>編輯機會</flux:button>
                    </div>
                </div>
            </div>

            @if(session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="mc-panel overflow-x-auto p-4">
                <div class="flex min-w-[720px] items-center gap-2">
                    @foreach($stages as $index => $pipelineStage)
                        <div class="flex-1 rounded-lg px-3 py-2 text-center text-xs font-semibold {{ $index <= $stageIndex ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ $stageLabels[$pipelineStage] ?? $pipelineStage }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_380px]">
                <div class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-4">
                        <div class="mc-stat">
                            <div class="text-xs font-medium text-slate-500">預計成交金額</div>
                            <div class="mt-1 text-xl font-semibold text-slate-950">HK${{ number_format((float) $record->estimated_deal_amount, 2) }}</div>
                        </div>
                        <div class="mc-stat">
                            <div class="text-xs font-medium text-slate-500">成功機率</div>
                            <div class="mt-1 text-xl font-semibold text-slate-950">{{ $record->probability }}%</div>
                        </div>
                        <div class="mc-stat">
                            <div class="text-xs font-medium text-slate-500">預計成交日期</div>
                            <div class="mt-1 text-xl font-semibold {{ $isOverdue ? 'text-rose-700' : 'text-slate-950' }}">{{ optional($record->expected_close_date)->format('Y-m-d') ?: '未設定' }}</div>
                        </div>
                        <div class="mc-stat">
                            <div class="text-xs font-medium text-slate-500">負責銷售</div>
                            <div class="mt-1 text-xl font-semibold text-slate-950">{{ $record->assignedSales?->name ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">下一步</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $nextStep }}</p>
                        @if($stage === 'Lost' && $record->lost_reason)
                            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                                流失原因：{{ $record->lost_reason }}
                            </div>
                        @endif
                    </div>

                    <div class="mc-panel p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">Trial Agreements</h2>
                                <p class="mt-1 text-sm text-slate-600">Generate the prefilled PDF and upload the signed copy after physical signing.</p>
                            </div>
                            <flux:button :href="route('trial-agreements.create', $record)" wire:navigate>Generate Trial Agreement</flux:button>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse($record->trialAgreements as $agreement)
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-950">{{ $agreement->document_number }}</div>
                                            <div class="text-slate-500">{{ $agreement->trial_start_date?->format('Y-m-d') }} to {{ $agreement->trial_end_date?->format('Y-m-d') }} · {{ $agreement->status }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                Assets: {{ $agreement->assets->pluck('asset_tag')->join(', ') ?: '-' }}
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @if($agreement->generated_pdf_path)
                                                <flux:button size="sm" variant="ghost" :href="route('trial-agreements.download', $agreement)">Download PDF</flux:button>
                                            @endif
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('trial-agreements.signed-copy', $agreement) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-2">
                                        @csrf
                                        <div class="min-w-64 flex-1">
                                            <label class="mb-1 block text-xs font-medium text-slate-500">Signed PDF</label>
                                            <input class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" type="file" name="signed_pdf" accept="application/pdf" required>
                                        </div>
                                        <flux:button type="submit" variant="ghost">Upload Signed Copy</flux:button>
                                    </form>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">No trial agreement generated yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">機會資料</h2>
                        <dl class="mt-4 grid gap-4 text-sm md:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-slate-500">客戶 / 商戶</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->account?->company_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">方案</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->salesPlan?->plan_name ?? '未選方案' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">最後更新</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->updated_at->format('Y-m-d H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-slate-500">建立日期</dt>
                                <dd class="mt-1 text-slate-950">{{ $record->created_at->format('Y-m-d H:i') }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs font-medium text-slate-500">備註</dt>
                                <dd class="mt-1 whitespace-pre-line text-slate-950">{{ $record->notes ?: '未填' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">Comment</h2>
                        <p class="mt-1 text-sm text-slate-600">Admin 與 Sales 溝通記錄會按時間保留。</p>
                        <form method="POST" action="{{ route('crm.comments.store', ['opportunities', $record->id]) }}" class="mt-4 space-y-3">
                            @csrf
                            <flux:textarea name="body" label="新增 Comment" required>{{ old('body') }}</flux:textarea>
                            <flux:button type="submit">新增 Comment</flux:button>
                        </form>
                        <div class="mt-5 space-y-4">
                            @forelse($record->comments as $comment)
                                <div class="border-l-2 border-slate-200 pl-3 text-sm">
                                    <div class="font-medium text-slate-950">{{ $comment->author?->name ?? '已刪除用戶' }}</div>
                                    <div class="mt-1 whitespace-pre-line text-slate-600">{{ $comment->body }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $comment->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">暫時沒有 Comment。</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <form method="POST" action="{{ route('crm.remarks', ['opportunities', $record->id]) }}" class="mc-panel p-5">
                        @csrf
                        <h2 class="text-base font-semibold text-slate-950">銷售備註 / Message</h2>
                        <p class="mt-1 text-sm text-slate-600">更新內部備註，例如客戶背景、報價阻力或下一步。</p>
                        <div class="mt-4 space-y-3">
                            <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
                            <flux:button type="submit" class="w-full">儲存備註</flux:button>
                        </div>
                    </form>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">快速操作</h2>
                        <div class="mt-4 grid gap-2">
                            @if($nextStage)
                                <form method="POST" action="{{ route('opportunities.stage', $record) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="advance">
                                    <flux:button type="submit" class="w-full">推進至 {{ $stageLabels[$nextStage] ?? $nextStage }}</flux:button>
                                </form>
                            @endif
                            @unless(in_array($stage, ['Done Deal', 'Lost'], true))
                                <form method="POST" action="{{ route('opportunities.stage', $record) }}" onsubmit="return confirm('確定要取消這個 OP？');">
                                    @csrf
                                    <input type="hidden" name="action" value="cancel">
                                    <flux:button type="submit" variant="danger" class="w-full">取消 OP</flux:button>
                                </form>
                            @endunless
                            <flux:button variant="ghost" :href="route('kanban')" wire:navigate>到看板</flux:button>
                            <flux:button variant="ghost" :href="route('crm.edit', ['opportunities', $record->id])" wire:navigate>更新金額 / 日期</flux:button>
                            <flux:button variant="ghost" :href="route('crm.show', ['accounts', $record->account_id])" wire:navigate>查看客戶帳戶</flux:button>
                        </div>
                    </div>

                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">跟進時間線</h2>
                        <div class="mt-4 space-y-4">
                            @forelse($record->activities as $activity)
                                <div class="border-l-2 border-teal-200 pl-3 text-sm">
                                    <div class="font-medium text-slate-950">{{ $activity->type }} · {{ $activity->user?->name }}</div>
                                    <div class="mt-1 text-slate-600">{{ $activity->body }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $activity->created_at->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">暫時沒有跟進記錄。之後可加入活動輸入功能，把通話、會議、試用結果記在這裡。</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ $displayTitle }} #{{ $record->id }}</flux:heading>
                    <flux:text>詳細資料及相關操作。</flux:text>
                </div>
                <flux:button :href="route('crm.edit', [$module, $record->id])" wire:navigate>編輯</flux:button>
            </div>
            @if(session('status'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
            <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
                @foreach($record->getAttributes() as $key => $value)
                    @unless(in_array($key, ['deleted_at'], true))
                        <div>
                            <div class="text-xs uppercase text-zinc-500">{{ $fieldLabels[$key] ?? str($key)->replace('_', ' ')->title() }}</div>
                            <div class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $formatDetail($key, $value) }}</div>
                        </div>
                    @endunless
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>
