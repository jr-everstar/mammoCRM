<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('mammo care CRM')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('儀表板') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office" :href="route('crm.index', 'accounts')" :current="request()->is('accounts*')" wire:navigate>{{ __('客戶 / 商戶') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('crm.index', 'contacts')" :current="request()->is('contacts*')" wire:navigate>{{ __('聯絡人 Contacts') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('crm.index', 'leads')" :current="request()->is('leads*')" wire:navigate>{{ __('商機 (Leads)') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('crm.index', 'opportunities')" :current="request()->is('opportunities*')" wire:navigate>{{ __('機會 (OP)') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="squares-2x2" :href="route('kanban')" :current="request()->routeIs('kanban')" wire:navigate>{{ __('銷售看板') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="archive-box" :href="route('assets.index')" :current="request()->is('inventory/assets*')" wire:navigate>{{ __('Assets') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('crm.index', 'deals')" :current="request()->is('deals*')" wire:navigate>{{ __('成交記錄') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="calculator" :href="route('commissions.index')" :current="request()->routeIs('commissions.index', 'commissions.show')" wire:navigate>{{ __('佣金計算') }}</flux:sidebar.item>
                    @if(auth()->user()->hasAnyRole(['admin', 'staff']))
                        <flux:sidebar.item icon="beaker" :href="route('commissions.simulator')" :current="request()->routeIs('commissions.simulator')" wire:navigate>{{ __('佣金模擬器') }}</flux:sidebar.item>
                    @endif
                    @role('admin')
                        <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->is('reports*')" wire:navigate>{{ __('報表') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('config.index', 'sales-plans')" :current="request()->is('config*')" wire:navigate>{{ __('系統設定') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="user-group" :href="route('admin.users.index')" :current="request()->is('admin/users*')" wire:navigate>{{ __('用戶管理') }}</flux:sidebar.item>
                    @endrole
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('設定') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('登出') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
