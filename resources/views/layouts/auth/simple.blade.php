<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-950 antialiased">
        <div class="grid min-h-svh lg:grid-cols-[1.05fr_0.95fr]">
            <section class="relative hidden overflow-hidden bg-slate-950 text-white lg:flex">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(15,118,110,.95),rgba(15,23,42,.92)_48%,rgba(136,19,55,.82))]"></div>
                <div class="absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(255,255,255,.12)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.12)_1px,transparent_1px)] [background-size:44px_44px]"></div>
                <div class="relative flex min-h-full w-full flex-col justify-between p-12">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold" wire:navigate>
                        <span class="flex size-12 items-center justify-center rounded-lg bg-white/95">
                            <img src="/assets/images/es-logo1024.png" alt="mammoCRM" class="size-9 object-contain">
                        </span>
                        <span>mammo care HK CRM</span>
                    </a>

                    <div class="max-w-xl">
                        <p class="text-sm font-medium uppercase text-teal-100">secure team workspace</p>
                        <h1 class="mt-4 text-4xl font-semibold leading-tight">Sales, client care, and commission work in one calm place.</h1>
                        <p class="mt-5 text-base leading-7 text-teal-50/85">Only approved team accounts can sign in. New users are created by an administrator.</p>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-white/15 bg-white/10 p-4">
                            <div class="text-2xl font-semibold">CRM</div>
                            <div class="mt-1 text-teal-50/75">Lead to deal</div>
                        </div>
                        <div class="rounded-lg border border-white/15 bg-white/10 p-4">
                            <div class="text-2xl font-semibold">2FA</div>
                            <div class="mt-1 text-teal-50/75">Account security</div>
                        </div>
                        <div class="rounded-lg border border-white/15 bg-white/10 p-4">
                            <div class="text-2xl font-semibold">HK</div>
                            <div class="mt-1 text-teal-50/75">Local workflow</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex min-h-svh flex-col items-center justify-center bg-slate-100 p-6 dark:bg-slate-950 md:p-10">
                <div class="w-full max-w-md rounded-xl border border-white/70 bg-white p-6 shadow-2xl shadow-slate-950/10 dark:border-slate-800 dark:bg-slate-900 md:p-8">
                    <a href="{{ route('home') }}" class="mb-7 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex size-12 items-center justify-center rounded-lg bg-slate-950">
                            <img src="/assets/images/es-logo1024.png" alt="mammoCRM" class="size-9 object-contain">
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    <div class="flex flex-col gap-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
