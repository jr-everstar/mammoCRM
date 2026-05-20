@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="mammo care HK" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-slate-200">
            <img src="/assets/images/es-logo1024.png" alt="mammoCRM" class="size-7 object-contain">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="mammo care HK" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-slate-200">
            <img src="/assets/images/es-logo1024.png" alt="mammoCRM" class="size-7 object-contain">
        </x-slot>
    </flux:brand>
@endif
