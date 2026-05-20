<x-layouts::app :title="$user->name">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex size-14 items-center justify-center rounded-lg bg-teal-600 text-lg font-semibold text-white">{{ $user->initials() }}</div>
                <div>
                    <flux:heading size="xl">{{ $user->name }}</flux:heading>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $user->email }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $user->status === 'active' ? '啟用' : '停用' }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $user->mustUseMicrosoftLogin() ? 'Microsoft Entra only' : 'Password / Passkey allowed' }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('admin.users.edit', $user)" variant="primary" icon="pencil-square" wire:navigate>編輯用戶</flux:button>
                <flux:button :href="route('admin.users.index')" variant="ghost" icon="arrow-left" wire:navigate>返回列表</flux:button>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if(session('invitation_link'))
            <section class="mc-panel-soft p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <flux:heading>邀請連結</flux:heading>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">邀請電郵已發送。管理員也可以複製以下連結，直接傳送給用戶。</p>
                        <input id="invitation-link" class="mt-3 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" value="{{ session('invitation_link') }}" readonly>
                    </div>
                    <flux:button type="button" icon="clipboard" onclick="navigator.clipboard.writeText(document.getElementById('invitation-link').value)">複製連結</flux:button>
                </div>
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="mc-panel p-5 lg:col-span-2">
                <flux:heading>帳戶資料</flux:heading>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase text-slate-500">姓名</dt><dd class="mt-1 font-semibold">{{ $user->name }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase text-slate-500">電郵</dt><dd class="mt-1 font-semibold">{{ $user->email }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase text-slate-500">狀態</dt><dd class="mt-1 font-semibold">{{ $user->status === 'active' ? '啟用' : '停用' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase text-slate-500">登入方式</dt><dd class="mt-1 font-semibold">{{ $user->mustUseMicrosoftLogin() ? 'Microsoft Entra' : '密碼 / Passkey' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase text-slate-500">建立時間</dt><dd class="mt-1 font-semibold">{{ $user->created_at?->format('Y-m-d H:i') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase text-slate-500">最後更新</dt><dd class="mt-1 font-semibold">{{ $user->updated_at?->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </section>

            <section class="mc-panel p-5">
                <flux:heading>角色權限</flux:heading>
                <div class="mt-5 flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                        <span class="rounded-full bg-teal-50 px-3 py-1.5 text-sm font-medium text-teal-700 dark:bg-teal-950 dark:text-teal-200">{{ $role->name }}</span>
                    @empty
                        <span class="text-sm text-slate-500">未分配角色</span>
                    @endforelse
                </div>
                <div class="mt-5 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <p>Admin 可管理設定及用戶。</p>
                    <p>Staff 可管理 CRM 記錄，但不可進入系統設定或用戶管理。</p>
                    <p>Sales 主要處理自己負責的商機、機會及成交。</p>
                </div>
            </section>
        </div>

        <section class="mc-panel p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading>邀請管理</flux:heading>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">重新發送邀請電郵，並產生新的可複製邀請連結。</p>
                </div>
                <form method="POST" action="{{ route('admin.users.invitation', $user) }}">
                    @csrf
                    <flux:button type="submit" icon="paper-airplane">重新發送邀請</flux:button>
                </form>
            </div>
        </section>
    </div>
</x-layouts::app>
