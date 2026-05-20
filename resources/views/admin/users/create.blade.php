<x-layouts::app :title="__('新增用戶')">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">新增用戶</flux:heading>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">建立內部帳戶、選擇一個角色，並發送邀請電郵。自助註冊已停用。</p>
            </div>
            <flux:button :href="route('admin.users.index')" variant="ghost" icon="arrow-left" wire:navigate>返回列表</flux:button>
        </div>

        @include('admin.users.form', [
            'user' => null,
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'submitLabel' => '建立用戶',
        ])
    </div>
</x-layouts::app>
