<x-layouts::auth :title="__('重設密碼')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('重設密碼')" :description="__('輸入電郵後，如帳戶符合資格，系統會發送密碼重設連結。')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('電郵地址')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('發送重設密碼電郵') }}
            </flux:button>
        </form>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-300">
            {{ __('電郵會經由 Mailgun 發送。Microsoft Entra-only 帳戶請使用 Microsoft 登入。') }}
        </div>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('返回') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('登入') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
