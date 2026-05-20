<x-layouts::app :title="$asset ? 'Edit Asset' : 'New Asset'">
    <div class="max-w-4xl space-y-5">
        <div>
            <flux:heading size="xl">{{ $asset ? 'Edit Asset' : 'New Asset' }}</flux:heading>
            <flux:text>For sensors, choose Left or Right. One trial sensor set is always one left sensor plus one right sensor.</flux:text>
        </div>

        @if($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ $asset ? route('assets.update', $asset) : route('assets.store') }}" class="mc-panel space-y-5 p-5" x-data="{ type: @js(old('type', $asset->type ?? 'ipad')) }">
            @csrf
            @if($asset) @method('PUT') @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <div class="text-xs font-medium text-slate-500">Asset Tag</div>
                    <div class="mt-1 font-semibold text-slate-950">{{ $asset?->asset_tag ?? 'Generated after save' }}</div>
                </div>
                <flux:input name="serial_number" label="Serial Number" value="{{ old('serial_number', $asset->serial_number ?? '') }}" />
                <flux:input name="model_name" label="Model / Description" value="{{ old('model_name', $asset->model_name ?? '') }}" />
                <flux:input name="location" label="Location" value="{{ old('location', $asset->location ?? '') }}" />
                <flux:select name="type" label="Type" x-model="type" required>
                    @foreach($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $asset->type ?? 'ipad') === $type)>{{ str($type)->headline() }}</option>
                    @endforeach
                </flux:select>
                <div x-show="type === 'sensor'" x-cloak>
                    <flux:select name="side" label="Sensor Side">
                        <option value="">Choose side</option>
                        @foreach($sensorSides as $side)
                            <option value="{{ $side }}" @selected(old('side', $asset->side ?? '') === $side)>{{ str($side)->title() }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:select name="status" label="Status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $asset->status ?? 'available') === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </flux:select>
                <flux:input name="condition" label="Condition" value="{{ old('condition', $asset->condition ?? 'good') }}" required />
            </div>

            <flux:textarea name="notes" label="Notes">{{ old('notes', $asset->notes ?? '') }}</flux:textarea>

            <div class="flex gap-3">
                <flux:button type="submit">Save Asset</flux:button>
                <flux:button variant="ghost" :href="route('assets.index')" wire:navigate>Cancel</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
