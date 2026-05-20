@php
    $record = $record ?? null;
    $moduleLabels = [
        'accounts' => '客戶 / 商戶帳戶',
        'contacts' => '聯絡人 Contacts',
        'leads' => '商機 (Leads)',
        'opportunities' => '機會 (OP)',
        'deals' => '成交記錄',
    ];
    $statusLabels = [
        'prospect' => '潛在客戶',
        'active' => '活躍',
        'inactive' => '停用',
        'lost' => '已流失',
        'New' => '新商機',
        'Contacted' => '已聯絡',
        'Meeting Scheduled' => '已約會議',
        'Demo Completed' => '已完成示範',
        'Trial Arranged' => '已安排試用',
        'Trial Completed' => '已完成試用',
        'Converted' => '已轉換',
        'Lost' => '已流失',
        'Pending' => '待處理',
        'Paid' => '已付款',
        'Cancelled' => '已取消',
        'Refunded' => '已退款',
        'Calculated' => '已計算',
        'Approved' => '已審批',
    ];
    $dealTypeLabels = [
        'new_deal' => '新成交',
        'passive_renewal_upgrade' => '被動續約 / 升級',
        'am_managed_renewal' => '客戶經理主導續約',
        'am_managed_upgrade' => '客戶經理主導升級',
    ];
    $displayTitle = $moduleLabels[$module] ?? $title;
@endphp
<x-layouts::app :title="$title">
    <div class="max-w-5xl space-y-5">
        <div>
            <flux:heading size="xl">{{ $record ? '編輯' : '新增' }}{{ $displayTitle }}</flux:heading>
            <flux:text>請按 mammo care HK 內部銷售流程填寫。未確認公司資料時，建議先建立商機 (Leads)。</flux:text>
        </div>

        @if($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ $record ? route('crm.update', [$module, $record->id]) : route('crm.store', $module) }}" class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            @csrf
            @if($record) @method('PUT') @endif

            @if($module === 'accounts')
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input name="company_name" label="公司名稱" value="{{ old('company_name', $record->company_name ?? '') }}" required />
                    <flux:input name="company_registration_number" label="公司註冊號碼" value="{{ old('company_registration_number', $record->company_registration_number ?? '') }}" required />
                    <flux:input name="business_type" label="業務類型" value="{{ old('business_type', $record->business_type ?? '') }}" />
                    <flux:input name="contact_person_name" label="聯絡人姓名" value="{{ old('contact_person_name', $record->contact_person_name ?? '') }}" />
                    <flux:input name="contact_person_title" label="聯絡人職銜" value="{{ old('contact_person_title', $record->contact_person_title ?? '') }}" />
                    <flux:input name="contact_phone" label="聯絡電話" value="{{ old('contact_phone', $record->contact_phone ?? '') }}" />
                    <flux:input name="contact_email" label="聯絡電郵" value="{{ old('contact_email', $record->contact_email ?? '') }}" />
                    <flux:select name="status" label="狀態">
                        @foreach(['prospect','active','inactive','lost'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $record->status ?? 'prospect') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </flux:select>
                    @if(auth()->user()->canManageCrm())
                        <flux:select name="account_manager_id" label="客戶經理">
                            @foreach($salesUsers as $salesUser)
                                <option value="{{ $salesUser->id }}" @selected(old('account_manager_id', $record->account_manager_id ?? auth()->id()) == $salesUser->id)>{{ $salesUser->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
                <flux:textarea name="address" label="地址">{{ old('address', $record->address ?? '') }}</flux:textarea>
                <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
            @elseif($module === 'contacts')
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select name="account_id" label="所屬客戶 / Account" required>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('account_id', $record->account_id ?? request('account_id')) == $account->id)>{{ $account->company_name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input name="name" label="聯絡人姓名" value="{{ old('name', $record->name ?? '') }}" required />
                    <flux:input name="title" label="職銜" value="{{ old('title', $record->title ?? '') }}" />
                    <flux:input name="phone" label="電話" value="{{ old('phone', $record->phone ?? '') }}" />
                    <flux:input name="email" label="電郵" value="{{ old('email', $record->email ?? '') }}" />
                    <flux:input name="whatsapp" label="WhatsApp" value="{{ old('whatsapp', $record->whatsapp ?? '') }}" />
                    <flux:select name="status" label="狀態">
                        @foreach(['active','inactive'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $record->status ?? 'active') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </flux:select>
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $record->is_primary ?? false))>
                        主要聯絡人
                    </label>
                </div>
                <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
            @elseif($module === 'leads')
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input name="lead_name" label="商機名稱" value="{{ old('lead_name', $record->lead_name ?? '') }}" required />
                    <flux:input name="company_name" label="公司名稱" value="{{ old('company_name', $record->company_name ?? '') }}" />
                    <flux:input name="company_registration_number" label="公司註冊號碼（未確認可留空）" value="{{ old('company_registration_number', $record->company_registration_number ?? '') }}" />
                    <flux:input name="contact_person" label="聯絡人" value="{{ old('contact_person', $record->contact_person ?? '') }}" />
                    <flux:input name="contact_phone" label="聯絡電話" value="{{ old('contact_phone', $record->contact_phone ?? '') }}" />
                    <flux:input name="contact_email" label="聯絡電郵" value="{{ old('contact_email', $record->contact_email ?? '') }}" />
                    <flux:input name="source" label="來源" value="{{ old('source', $record->source ?? '') }}" />
                    <flux:input name="business_type" label="業務類型" value="{{ old('business_type', $record->business_type ?? '') }}" />
                    <flux:select name="status" label="狀態">
                        @foreach(['New','Contacted','Meeting Scheduled','Demo Completed','Trial Arranged','Trial Completed','Converted','Lost'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $record->status ?? 'New') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </flux:select>
                    @if(auth()->user()->canManageCrm())
                        <flux:select name="assigned_sales_id" label="負責銷售">
                            @foreach($salesUsers as $salesUser)
                                <option value="{{ $salesUser->id }}" @selected(old('assigned_sales_id', $record->assigned_sales_id ?? auth()->id()) == $salesUser->id)>{{ $salesUser->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
                <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
            @elseif($module === 'opportunities')
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select name="account_id" label="客戶 / 商戶帳戶" required>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('account_id', $record->account_id ?? '') == $account->id)>{{ $account->company_name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input name="opportunity_name" label="機會名稱" value="{{ old('opportunity_name', $record->opportunity_name ?? '') }}" required />
                    <flux:select name="sales_plan_id" label="銷售方案" required>
                        <option value="">請選擇方案</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('sales_plan_id', $record->sales_plan_id ?? '') == $plan->id)>{{ $plan->plan_name }} · HK${{ number_format((float) $plan->selling_price) }}</option>
                        @endforeach
                    </flux:select>
                    <div class="rounded-lg border border-teal-100 bg-teal-50 p-3 text-sm text-teal-800">
                        OP 金額會自動使用所選套餐的固定售價；不用手動填寫，避免輸入錯誤。
                        @if($record?->salesPlan)
                            <div class="mt-1 font-semibold">目前：{{ $record->salesPlan->plan_name }} · HK${{ number_format((float) $record->salesPlan->selling_price) }}</div>
                        @endif
                    </div>
                    <div class="rounded-lg border border-sky-100 bg-sky-50 p-3 text-sm text-sky-800">
                        成功機率會根據 OP 階段自動設定，請到「系統設定 → OP 階段機率」調整 mapping。
                        @if($record)
                            <div class="mt-1 font-semibold">目前階段機率：{{ $record->probability }}%</div>
                        @endif
                    </div>
                    <flux:input name="expected_close_date" type="date" label="預計成交日期" value="{{ old('expected_close_date', optional($record->expected_close_date ?? null)->format('Y-m-d')) }}" />
                    <flux:select name="stage" label="銷售階段">
                        @foreach($stages as $stage)
                            <option value="{{ $stage }}" @selected(old('stage', $record->stage ?? 'Lead-in') === $stage)>{{ $stage }}</option>
                        @endforeach
                    </flux:select>
                    @if(auth()->user()->canManageCrm())
                        <flux:select name="assigned_sales_id" label="負責銷售">
                            @foreach($salesUsers as $salesUser)
                                <option value="{{ $salesUser->id }}" @selected(old('assigned_sales_id', $record->assigned_sales_id ?? auth()->id()) == $salesUser->id)>{{ $salesUser->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
                <flux:textarea name="lost_reason" label="流失原因">{{ old('lost_reason', $record->lost_reason ?? '') }}</flux:textarea>
                <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
            @else
                @php
                    $requestedDealType = old('deal_type', $record->deal_type ?? request('deal_type', 'new_deal'));
                    $isRenewalDeal = in_array($requestedDealType, ['passive_renewal_upgrade', 'am_managed_renewal', 'am_managed_upgrade'], true);
                    $derivedDealType = $isRenewalDeal
                        ? (auth()->user()->hasRole('sales') && ! auth()->user()->canManageCrm() ? 'am_managed_renewal' : 'passive_renewal_upgrade')
                        : 'new_deal';
                @endphp
                <div class="rounded-lg border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800">
                    <div class="font-semibold">續約 / 升級 Deal 指引</div>
                    <div class="mt-1">續約 Deal 需要該 Account 已有成功 Done Deal。Sales 新增會自動列為 AM Renewal；Staff / Admin 新增會自動列為 Passive Renewal。</div>
                    <div class="mt-1">New Deal 金額會用套餐固定價；Renewal / Upgrade 可按實際付款金額修改。</div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select name="account_id" label="客戶 / 商戶帳戶">@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('account_id', $record->account_id ?? request('account_id')) == $account->id)>{{ $account->company_name }}</option>@endforeach</flux:select>
                    <flux:select name="sales_plan_id" label="銷售方案">@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('sales_plan_id', $record->sales_plan_id ?? '') == $plan->id)>{{ $plan->plan_name }} · HK${{ number_format((float) $plan->selling_price) }}</option>@endforeach</flux:select>
                    <flux:select name="sales_user_id" label="負責銷售">@foreach($salesUsers as $salesUser)<option value="{{ $salesUser->id }}" @selected(old('sales_user_id', $record->sales_user_id ?? auth()->id()) == $salesUser->id)>{{ $salesUser->name }}</option>@endforeach</flux:select>
                    <flux:select name="account_manager_id" label="客戶經理">@foreach($salesUsers as $salesUser)<option value="{{ $salesUser->id }}" @selected(old('account_manager_id', $record->account_manager_id ?? auth()->id()) == $salesUser->id)>{{ $salesUser->name }}</option>@endforeach</flux:select>
                    <input type="hidden" name="deal_type" value="{{ $derivedDealType }}">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                        <div class="text-xs font-medium text-slate-500">成交類型</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $dealTypeLabels[$derivedDealType] ?? $derivedDealType }}</div>
                    </div>
                    <flux:input name="deal_amount" type="number" step="0.01" label="成交金額 (HK$)" value="{{ old('deal_amount', $record->deal_amount ?? 0) }}" />
                    <flux:select name="payment_status" label="付款狀態">@foreach(['Pending','Paid','Cancelled','Refunded'] as $status)<option value="{{ $status }}" @selected(old('payment_status', $record->payment_status ?? 'Pending') === $status)>{{ $statusLabels[$status] ?? $status }}</option>@endforeach</flux:select>
                    <flux:input name="payment_date" type="date" label="付款日期" value="{{ old('payment_date', optional($record->payment_date ?? null)->format('Y-m-d')) }}" />
                    <flux:input name="contract_date" type="date" label="合約日期" value="{{ old('contract_date', optional($record->contract_date ?? null)->format('Y-m-d')) }}" />
                    <flux:select name="commission_status" label="佣金狀態">@foreach(['Pending','Calculated','Approved','Paid','Cancelled'] as $status)<option value="{{ $status }}" @selected(old('commission_status', $record->commission_status ?? 'Pending') === $status)>{{ $statusLabels[$status] ?? $status }}</option>@endforeach</flux:select>
                </div>
                <flux:textarea name="notes" label="備註">{{ old('notes', $record->notes ?? '') }}</flux:textarea>
            @endif

            <div class="flex gap-3">
                <flux:button type="submit">儲存</flux:button>
                <flux:button variant="ghost" :href="route('crm.index', $module)" wire:navigate>取消</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
