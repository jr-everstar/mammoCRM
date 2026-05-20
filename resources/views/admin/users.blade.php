<x-layouts::app :title="__('用戶管理')">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <flux:heading size="xl">用戶管理</flux:heading>
                <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                    管理員建立帳戶、分配角色、停用離職用戶，並檢查登入方式。自助註冊已停用。
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('admin.users.create')" variant="primary" icon="plus" wire:navigate>新增用戶</flux:button>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">全部用戶</div><div class="mt-2 text-2xl font-semibold">{{ $counts['total'] }}</div></div>
            <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">啟用</div><div class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $counts['active'] }}</div></div>
            <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">停用</div><div class="mt-2 text-2xl font-semibold text-slate-500">{{ $counts['inactive'] }}</div></div>
            <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">管理員</div><div class="mt-2 text-2xl font-semibold">{{ $counts['admins'] }}</div></div>
            <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">Staff</div><div class="mt-2 text-2xl font-semibold">{{ $counts['staff'] }}</div></div>
            <div class="mc-stat"><div class="text-xs font-medium uppercase text-slate-500">銷售</div><div class="mt-2 text-2xl font-semibold">{{ $counts['sales'] }}</div></div>
        </div>

        <section class="mc-panel overflow-hidden">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 lg:grid-cols-[1fr_180px_180px_auto] lg:items-end">
                    <flux:input name="q" label="搜尋" value="{{ $filters['q'] ?? '' }}" placeholder="姓名或電郵" />
                    <flux:select name="role" label="角色">
                        <option value="">全部角色</option>
                        <option value="sales" @selected(($filters['role'] ?? '') === 'sales')>銷售</option>
                        <option value="staff" @selected(($filters['role'] ?? '') === 'staff')>Staff</option>
                        <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>管理員</option>
                    </flux:select>
                    <flux:select name="status" label="狀態">
                        <option value="">全部狀態</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>啟用</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>停用</option>
                    </flux:select>
                    <div class="flex gap-2">
                        <flux:button type="submit" icon="magnifying-glass">篩選</flux:button>
                        @if(($filters['q'] ?? null) || ($filters['role'] ?? null) || ($filters['status'] ?? null))
                            <flux:button :href="route('admin.users.index')" variant="ghost" wire:navigate>重設</flux:button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500 dark:border-slate-800 dark:bg-slate-900/70">
                        <tr>
                            <th class="px-5 py-3">用戶</th>
                            <th class="px-5 py-3">角色</th>
                            <th class="px-5 py-3">狀態</th>
                            <th class="px-5 py-3">登入方式</th>
                            <th class="px-5 py-3">建立時間</th>
                            <th class="px-5 py-3 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($users as $user)
                            <tr class="bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800/70">
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.users.show', $user) }}" class="font-semibold text-slate-950 hover:text-teal-700 dark:text-white dark:hover:text-teal-300" wire:navigate>{{ $user->name }}</a>
                                    <div class="mt-1 text-slate-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($user->roles as $role)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $user->status === 'active' ? '啟用' : '停用' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">{{ $user->mustUseMicrosoftLogin() ? 'Microsoft Entra' : '密碼 / Passkey' }}</td>
                                <td class="px-5 py-4 text-slate-500">{{ $user->created_at?->format('Y-m-d') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" :href="route('admin.users.show', $user)" icon="eye" wire:navigate>查看</flux:button>
                                        <flux:button size="sm" :href="route('admin.users.edit', $user)" icon="pencil-square" wire:navigate>編輯</flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">沒有符合條件的用戶。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $users->links() }}
    </div>
</x-layouts::app>
