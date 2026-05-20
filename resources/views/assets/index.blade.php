<x-layouts::app title="Assets">
    @php
        $typeLabels = [
            'ipad' => 'iPad',
            'sensor' => 'Sensor',
            'charger' => 'Charger',
            'cable' => 'Cable',
            'case' => 'Case',
            'accessory' => 'Accessory',
            'other' => 'Other',
        ];
        $statusLabels = [
            'available' => 'Available',
            'reserved' => 'Reserved',
            'on_trial' => 'On Trial',
            'maintenance' => 'Maintenance',
            'lost' => 'Lost',
            'retired' => 'Retired',
        ];
        $statusClass = fn ($status) => match($status) {
            'available' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'reserved', 'on_trial' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'maintenance' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'lost' => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-slate-50 text-slate-600 ring-slate-200',
        };
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">Assets</flux:heading>
                <flux:text>Track loanable iPads, left/right sensors, chargers, cables, and trial accessories.</flux:text>
            </div>
            @if(auth()->user()->canManageCrm())
                <flux:button :href="route('assets.create')" wire:navigate>新增 Asset</flux:button>
            @endif
        </div>

        @if(session('status'))
            <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <form class="mc-panel flex flex-wrap items-end gap-3 p-4">
            <flux:select name="type" label="Type">
                <option value="">All types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $typeLabels[$type] ?? $type }}</option>
                @endforeach
            </flux:select>
            <flux:select name="status" label="Status">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                @endforeach
            </flux:select>
            <flux:button type="submit">Filter</flux:button>
        </form>

        <div class="mc-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Asset</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Side</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Condition</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($assets as $asset)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-950">{{ $asset->asset_tag }}</div>
                                    <div class="text-xs text-slate-500">{{ $asset->serial_number ?: 'No serial' }} · {{ $asset->model_name ?: 'No model' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $typeLabels[$asset->type] ?? $asset->type }}</td>
                                <td class="px-4 py-3">{{ $asset->side ? str($asset->side)->title() : '-' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass($asset->status) }}">{{ $statusLabels[$asset->status] ?? $asset->status }}</span></td>
                                <td class="px-4 py-3">{{ $asset->condition }}</td>
                                <td class="px-4 py-3">{{ $asset->location ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if(auth()->user()->canManageCrm())
                                        <a class="font-medium text-teal-700" href="{{ route('assets.edit', $asset) }}" wire:navigate>Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No assets yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $assets->links() }}</div>
        </div>
    </div>
</x-layouts::app>
