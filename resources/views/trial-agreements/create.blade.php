<x-layouts::app title="Generate Trial Agreement">
    @php
        $plan = $opportunity->salesPlan;
        $requiredIpads = 1;
        $requiredSensorSets = 1;
    @endphp

    <div class="max-w-6xl space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="xl">Generate Trial Agreement</flux:heading>
                <flux:text>{{ $opportunity->opportunity_name }} · {{ $opportunity->account->company_name }} · {{ $plan?->plan_name ?? 'No plan' }}</flux:text>
            </div>
            <flux:button variant="ghost" :href="route('crm.show', ['opportunities', $opportunity->id])" wire:navigate>Back to Opportunity</flux:button>
        </div>

        @if($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <div class="mc-panel-soft p-4 text-sm text-slate-700">
            Trial loan hardware: <span class="font-semibold">{{ $requiredIpads }} iPad</span> and
            <span class="font-semibold">{{ $requiredSensorSets }} sensor set(s)</span>.
            This is fixed for every trial no matter which sales plan the client is considering.
        </div>

        <form method="POST" action="{{ route('trial-agreements.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="opportunity_id" value="{{ $opportunity->id }}">

            <div class="grid gap-5 lg:grid-cols-[1fr_380px]">
                <div class="space-y-5">
                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">Agreement Details</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <flux:select name="contact_id" label="Client Contact">
                                <option value="">Use account contact fields</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" @selected(old('contact_id') == $contact->id)>{{ $contact->name }} · {{ $contact->title ?: 'No title' }}</option>
                                @endforeach
                            </flux:select>
                            <flux:input name="effective_date" type="date" label="Effective Date" value="{{ old('effective_date', now()->toDateString()) }}" required />
                            <flux:input name="trial_start_date" type="date" label="Trial Start Date" value="{{ old('trial_start_date', now()->toDateString()) }}" required />
                            <flux:input name="trial_end_date" type="date" label="Trial End Date" value="{{ old('trial_end_date', now()->addDays(7)->toDateString()) }}" required />
                            <flux:input name="trial_location" label="Trial Location" value="{{ old('trial_location', $opportunity->account->address) }}" />
                            <flux:input name="client_coordinator_name" label="Client Coordinator" value="{{ old('client_coordinator_name', $opportunity->account->contact_person_name) }}" />
                            <flux:input name="client_coordinator_title" label="Client Coordinator Title" value="{{ old('client_coordinator_title', $opportunity->account->contact_person_title) }}" />
                            <flux:input name="client_coordinator_email" label="Client Coordinator Email" value="{{ old('client_coordinator_email', $opportunity->account->contact_email) }}" />
                            <flux:input name="client_coordinator_phone" label="Client Coordinator Phone" value="{{ old('client_coordinator_phone', $opportunity->account->contact_phone) }}" />
                            <flux:input name="delivery_method" label="Delivery Method" value="{{ old('delivery_method') }}" />
                            <flux:input name="return_method" label="Return Method" value="{{ old('return_method') }}" />
                            <flux:input name="return_address" label="Return Address" value="{{ old('return_address', $defaults['default_return_address']) }}" />
                            <flux:input name="trial_fee" label="Trial Fee" value="{{ old('trial_fee', $defaults['default_trial_fee']) }}" />
                            <flux:input name="security_deposit" label="Security Deposit" value="{{ old('security_deposit', $defaults['default_security_deposit']) }}" />
                        </div>
                        <flux:textarea class="mt-4" name="special_conditions" label="Special Conditions">{{ old('special_conditions') }}</flux:textarea>
                    </div>

                    <div class="mc-panel p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-950">System Assigned Assets</h2>
                                <p class="mt-1 text-sm text-slate-600">These assets will be reserved automatically when the agreement is generated.</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $hasRequiredAssets ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                {{ $hasRequiredAssets ? 'Ready to generate' : 'Missing assets' }}
                            </span>
                        </div>

                        @unless($hasRequiredAssets)
                            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                                Cannot generate until enough available iPads and left/right sensor pairs are in inventory.
                            </div>
                        @endunless

                        <div class="mt-4 grid gap-4 xl:grid-cols-3">
                            @foreach([
                                'iPads' => [$ipads, $requiredIpads, 'iPad'],
                                'Left Sensors' => [$leftSensors, $requiredSensorSets, 'Left sensor'],
                                'Right Sensors' => [$rightSensors, $requiredSensorSets, 'Right sensor'],
                            ] as $label => [$assets, $required, $fallbackModel])
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-2 text-sm">
                                        <span class="font-semibold text-slate-800">{{ $label }}</span>
                                        <span class="{{ $assets->count() >= $required ? 'text-emerald-700' : 'text-rose-700' }} text-xs font-semibold">{{ $assets->count() }} / {{ $required }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse($assets as $asset)
                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                                <div class="font-semibold text-slate-950">{{ $asset->asset_tag }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $asset->serial_number ?: 'No serial' }} · {{ $asset->model_name ?: $fallbackModel }}</div>
                                            </div>
                                        @empty
                                            <div class="rounded-lg border border-dashed border-slate-200 p-3 text-sm text-slate-500">No available {{ strtolower($label) }}.</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="mc-panel p-5">
                        <h2 class="text-base font-semibold text-slate-950">Prefill Source</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs font-medium text-slate-500">Client</dt><dd class="mt-1 text-slate-950">{{ $opportunity->account->company_name }}</dd></div>
                            <div><dt class="text-xs font-medium text-slate-500">BR / CR No.</dt><dd class="mt-1 text-slate-950">{{ $opportunity->account->company_registration_number }}</dd></div>
                            <div><dt class="text-xs font-medium text-slate-500">Address</dt><dd class="mt-1 text-slate-950">{{ $opportunity->account->address ?: '-' }}</dd></div>
                            <div><dt class="text-xs font-medium text-slate-500">EverStar AM</dt><dd class="mt-1 text-slate-950">{{ $opportunity->assignedSales?->name ?: '-' }}</dd></div>
                        </dl>
                    </div>
                    <flux:button type="submit" class="w-full" @disabled(! $hasRequiredAssets)>Generate PDF</flux:button>
                </div>
            </div>
        </form>
    </div>
</x-layouts::app>
