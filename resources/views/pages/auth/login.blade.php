<x-layouts::auth :title="__('登入')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('登入 mammo care HK CRM')" :description="__('請輸入電郵及密碼')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <a
            href="{{ route('auth.microsoft.redirect') }}"
            class="group flex min-h-12 w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-950"
        >
            <span class="grid size-5 shrink-0 grid-cols-2 gap-0.5" aria-hidden="true">
                <span class="rounded-[1px] bg-[#f25022]"></span>
                <span class="rounded-[1px] bg-[#7fba00]"></span>
                <span class="rounded-[1px] bg-[#00a4ef]"></span>
                <span class="rounded-[1px] bg-[#ffb900]"></span>
            </span>
            <span>{{ __('Continue with Microsoft Entra') }}</span>
        </a>

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('電郵地址')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('密碼')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('密碼')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('忘記密碼？') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('記住我')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('登入') }}
                </flux:button>
            </div>
        </form>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-300">
            {{ __('需要帳戶？請聯絡管理員建立。') }}
        </div>
    </div>
</x-layouts::auth>
