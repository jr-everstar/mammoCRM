<x-layouts::app :title="__('編輯用戶')">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">編輯用戶</flux:heading>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $user->name }} · {{ $user->email }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('admin.users.show', $user)" variant="ghost" icon="eye" wire:navigate>查看</flux:button>
                <flux:button :href="route('admin.users.index')" variant="ghost" icon="arrow-left" wire:navigate>返回列表</flux:button>
            </div>
        </div>

        @include('admin.users.form', [
            'user' => $user,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'submitLabel' => '儲存變更',
        ])
    </div>
</x-layouts::app>
