@php
    $selectedRole = old('role', $user?->roles->pluck('name')->first() ?? 'sales');
    $mustUseMicrosoft = old('must_use_microsoft_login', $user?->mustUseMicrosoftLogin() ? '1' : null);
@endphp

<form method="POST" action="{{ $action }}" class="mc-panel overflow-hidden">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    @if($errors->any())
        <div class="border-b border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200">
            請檢查表格資料：{{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-5 p-5 md:grid-cols-2">
        <flux:input name="name" label="姓名" value="{{ old('name', $user?->name) }}" required />
        <flux:input name="email" type="email" label="電郵" value="{{ old('email', $user?->email) }}" required />

        @if($user)
            <flux:select name="status" label="狀態">
                <option value="active" @selected(old('status', $user->status) === 'active')>啟用</option>
                <option value="inactive" @selected(old('status', $user->status) === 'inactive')>停用</option>
            </flux:select>
        @endif

        <div>
            <div class="mb-2 text-sm font-medium">角色</div>
            <div class="grid gap-2 rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                <label class="inline-flex items-start gap-2 text-sm"><input class="mt-1" type="radio" name="role" value="sales" @checked($selectedRole === 'sales')><span><strong>Sales</strong><br><span class="text-xs text-slate-500">處理自己負責的銷售資料。</span></span></label>
                <label class="inline-flex items-start gap-2 text-sm"><input class="mt-1" type="radio" name="role" value="staff" @checked($selectedRole === 'staff')><span><strong>Staff</strong><br><span class="text-xs text-slate-500">可管理 CRM 記錄，不可管理系統設定。</span></span></label>
                <label class="inline-flex items-start gap-2 text-sm"><input class="mt-1" type="radio" name="role" value="admin" @checked($selectedRole === 'admin')><span><strong>Admin</strong><br><span class="text-xs text-slate-500">完整系統設定及用戶管理權限。</span></span></label>
            </div>
            <p class="mt-2 text-xs text-slate-500">每位用戶只可選擇一個角色。</p>
        </div>

        <div>
            <div class="mb-2 text-sm font-medium">登入政策</div>
            <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
                <input class="mt-1" type="checkbox" name="must_use_microsoft_login" value="1" @checked((bool) $mustUseMicrosoft)>
                <span>
                    <strong>必須使用 Microsoft Entra 登入</strong>
                    <span class="mt-1 block text-xs text-slate-500">啟用後，本地密碼不供登入；儲存時會移除現有 passkeys。</span>
                </span>
            </label>
        </div>

        <div class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm text-teal-800 dark:border-teal-900/60 dark:bg-teal-950/30 dark:text-teal-100">
            建立用戶後，系統會自動發送邀請電郵。一般用戶會收到設定密碼連結；Microsoft Entra-only 用戶會收到登入入口。
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70">
        <flux:button :href="$user ? route('admin.users.show', $user) : route('admin.users.index')" variant="ghost" wire:navigate>取消</flux:button>
        <flux:button type="submit" variant="primary" icon="check">{{ $submitLabel }}</flux:button>
    </div>
</form>
