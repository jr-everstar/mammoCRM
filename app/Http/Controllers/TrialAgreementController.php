<?php

namespace App\Http\Controllers;

use App\Models\AccountDocument;
use App\Models\Asset;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\TrialAgreement;
use App\Models\TrialSetting;
use App\Services\TrialAgreements\TrialAgreementDocumentGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class TrialAgreementController extends Controller
{
    public function create(Request $request, Opportunity $opportunity): View
    {
        $this->authorizeOpportunity($request, $opportunity);

        $opportunity->load(['account.contacts', 'salesPlan', 'assignedSales']);
        $requiredIpads = $this->requiredTrialIpads();
        $requiredSensorSets = $this->requiredTrialSensorSets();
        $ipads = Asset::available()->where('type', 'ipad')->orderBy('asset_tag')->limit($requiredIpads)->get();
        $leftSensors = Asset::available()->where('type', 'sensor')->where('side', 'left')->orderBy('asset_tag')->limit($requiredSensorSets)->get();
        $rightSensors = Asset::available()->where('type', 'sensor')->where('side', 'right')->orderBy('asset_tag')->limit($requiredSensorSets)->get();

        return view('trial-agreements.create', [
            'opportunity' => $opportunity,
            'contacts' => $opportunity->account->contacts()->where('status', 'active')->orderByDesc('is_primary')->orderBy('name')->get(),
            'ipads' => $ipads,
            'leftSensors' => $leftSensors,
            'rightSensors' => $rightSensors,
            'hasRequiredAssets' => $ipads->count() >= $requiredIpads
                && $leftSensors->count() >= $requiredSensorSets
                && $rightSensors->count() >= $requiredSensorSets,
            'defaults' => $this->defaults(),
        ]);
    }

    public function store(Request $request, TrialAgreementDocumentGenerator $generator): RedirectResponse
    {
        $data = $request->validate([
            'opportunity_id' => ['required', 'exists:opportunities,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'effective_date' => ['required', 'date'],
            'trial_start_date' => ['required', 'date'],
            'trial_end_date' => ['required', 'date', 'after_or_equal:trial_start_date'],
            'trial_location' => ['nullable', 'string', 'max:255'],
            'client_coordinator_name' => ['nullable', 'string', 'max:255'],
            'client_coordinator_title' => ['nullable', 'string', 'max:255'],
            'client_coordinator_email' => ['nullable', 'email', 'max:255'],
            'client_coordinator_phone' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['nullable', 'string', 'max:255'],
            'return_method' => ['nullable', 'string', 'max:255'],
            'return_address' => ['nullable', 'string', 'max:255'],
            'trial_fee' => ['nullable', 'string', 'max:255'],
            'security_deposit' => ['nullable', 'string', 'max:255'],
            'special_conditions' => ['nullable', 'string'],
        ]);

        $opportunity = Opportunity::with(['account', 'salesPlan'])->findOrFail($data['opportunity_id']);
        $this->authorizeOpportunity($request, $opportunity);

        if (! empty($data['contact_id'])) {
            $contact = Contact::where('account_id', $opportunity->account_id)->findOrFail($data['contact_id']);
            $data['client_coordinator_name'] = $data['client_coordinator_name'] ?: $contact->name;
            $data['client_coordinator_title'] = $data['client_coordinator_title'] ?: $contact->title;
            $data['client_coordinator_email'] = $data['client_coordinator_email'] ?: $contact->email;
            $data['client_coordinator_phone'] = $data['client_coordinator_phone'] ?: $contact->phone;
        }

        try {
            $agreement = DB::transaction(function () use ($data, $opportunity, $request, $generator): TrialAgreement {
                $assets = $this->autoAssignAssets($opportunity);
                $assetIds = $assets->pluck('id')->all();

                $agreement = TrialAgreement::create([
                    'account_id' => $opportunity->account_id,
                    'opportunity_id' => $opportunity->id,
                    'contact_id' => $data['contact_id'] ?? null,
                    'sales_plan_id' => $opportunity->sales_plan_id,
                    'sales_user_id' => $opportunity->assigned_sales_id,
                    'account_manager_id' => $opportunity->account->account_manager_id,
                    'generated_by' => $request->user()->id,
                    'document_number' => $this->nextDocumentNumber(),
                    'effective_date' => $data['effective_date'],
                    'trial_start_date' => $data['trial_start_date'],
                    'trial_end_date' => $data['trial_end_date'],
                    'trial_location' => $data['trial_location'] ?? $opportunity->account->address,
                    'client_coordinator_name' => $data['client_coordinator_name'] ?? null,
                    'client_coordinator_title' => $data['client_coordinator_title'] ?? null,
                    'client_coordinator_email' => $data['client_coordinator_email'] ?? null,
                    'client_coordinator_phone' => $data['client_coordinator_phone'] ?? null,
                    'delivery_method' => $data['delivery_method'] ?? null,
                    'return_method' => $data['return_method'] ?? null,
                    'return_address' => $data['return_address'] ?? $this->setting('default_return_address'),
                    'trial_fee' => $data['trial_fee'] ?? $this->setting('default_trial_fee', 'Waived'),
                    'security_deposit' => $data['security_deposit'] ?? $this->setting('default_security_deposit', 'N/A'),
                    'special_conditions' => $data['special_conditions'] ?? null,
                    'everstar_address' => $this->setting('everstar_address'),
                    'director_name' => $this->setting('director_name'),
                    'generated_at' => now(),
                ]);

                foreach ($assets as $asset) {
                    $agreement->assets()->attach($asset->id, [
                        'role' => $this->assetRole($asset),
                        'condition_at_handover' => $asset->condition,
                    ]);
                }

                [$docxPath, $pdfPath] = $generator->generate($agreement);

                $agreement->update([
                    'generated_docx_path' => $docxPath,
                    'generated_pdf_path' => $pdfPath,
                ]);

                AccountDocument::create([
                    'account_id' => $agreement->account_id,
                    'opportunity_id' => $agreement->opportunity_id,
                    'type' => 'trial_agreement',
                    'document_number' => $agreement->document_number,
                    'title' => 'Trial Agreement - '.$agreement->opportunity->opportunity_name,
                    'amount' => 0,
                    'status' => 'generated',
                    'document_date' => $agreement->effective_date,
                    'due_date' => $agreement->trial_end_date,
                    'notes' => 'Generated from Opportunity trial workflow.',
                    'generated_file_path' => $pdfPath,
                ]);

                Asset::whereIn('id', $assetIds)->update(['status' => 'reserved']);

                return $agreement;
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['document' => $exception->getMessage()])->withInput();
        }

        return to_route('crm.show', ['opportunities', $agreement->opportunity_id])->with('status', 'Trial agreement generated.');
    }

    public function download(Request $request, TrialAgreement $trialAgreement)
    {
        $this->authorizeAgreement($request, $trialAgreement);

        abort_if(blank($trialAgreement->generated_pdf_path) || ! Storage::disk('local')->exists($trialAgreement->generated_pdf_path), 404);

        return Storage::disk('local')->download($trialAgreement->generated_pdf_path, $trialAgreement->document_number.'.pdf');
    }

    public function uploadSignedCopy(Request $request, TrialAgreement $trialAgreement): RedirectResponse
    {
        $this->authorizeAgreement($request, $trialAgreement);

        $data = $request->validate([
            'signed_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $path = $data['signed_pdf']->storeAs(
            'trial-agreements/'.$trialAgreement->id.'/signed',
            $trialAgreement->document_number.'-signed.pdf',
            'local'
        );

        $trialAgreement->update([
            'status' => 'signed',
            'signed_pdf_path' => $path,
            'signed_uploaded_at' => now(),
            'signed_uploaded_by' => $request->user()->id,
        ]);

        AccountDocument::where('type', 'trial_agreement')
            ->where('document_number', $trialAgreement->document_number)
            ->update([
                'status' => 'signed',
                'signed_file_path' => $path,
            ]);

        return back()->with('status', 'Signed trial agreement uploaded.');
    }

    private function autoAssignAssets(Opportunity $opportunity)
    {
        $requiredIpads = $this->requiredTrialIpads();
        $requiredSensorSets = $this->requiredTrialSensorSets();
        $ipads = Asset::available()
            ->where('type', 'ipad')
            ->orderBy('asset_tag')
            ->limit($requiredIpads)
            ->lockForUpdate()
            ->get();
        $leftSensors = Asset::available()
            ->where('type', 'sensor')
            ->where('side', 'left')
            ->orderBy('asset_tag')
            ->limit($requiredSensorSets)
            ->lockForUpdate()
            ->get();
        $rightSensors = Asset::available()
            ->where('type', 'sensor')
            ->where('side', 'right')
            ->orderBy('asset_tag')
            ->limit($requiredSensorSets)
            ->lockForUpdate()
            ->get();

        if ($ipads->count() < $requiredIpads) {
            throw ValidationException::withMessages(['assets' => "Cannot generate: this plan requires {$requiredIpads} available iPad(s)."]);
        }

        if ($leftSensors->count() < $requiredSensorSets || $rightSensors->count() < $requiredSensorSets) {
            throw ValidationException::withMessages(['assets' => "Cannot generate: this plan requires {$requiredSensorSets} available left/right sensor set(s)."]);
        }

        return $ipads->concat($leftSensors)->concat($rightSensors);
    }

    private function assetRole(Asset $asset): string
    {
        return $asset->type === 'sensor'
            ? 'sensor_'.$asset->side
            : $asset->type;
    }

    private function requiredTrialIpads(): int
    {
        return 1;
    }

    private function requiredTrialSensorSets(): int
    {
        return 1;
    }

    private function nextDocumentNumber(): string
    {
        $prefix = 'TA-'.now()->format('Y');
        $next = TrialAgreement::where('document_number', 'like', $prefix.'-%')->count() + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (TrialAgreement::where('document_number', $number)->exists());

        return $number;
    }

    private function authorizeOpportunity(Request $request, Opportunity $opportunity): void
    {
        abort_unless($request->user()->canManageCrm() || $opportunity->assigned_sales_id === $request->user()->id, 403);
    }

    private function authorizeAgreement(Request $request, TrialAgreement $agreement): void
    {
        abort_unless($request->user()->canManageCrm() || $agreement->sales_user_id === $request->user()->id || $agreement->account_manager_id === $request->user()->id, 403);
    }

    private function defaults(): array
    {
        return [
            'everstar_address' => $this->setting('everstar_address'),
            'default_return_address' => $this->setting('default_return_address'),
            'default_trial_fee' => $this->setting('default_trial_fee', 'Waived'),
            'default_security_deposit' => $this->setting('default_security_deposit', 'N/A'),
            'director_name' => $this->setting('director_name'),
        ];
    }

    private function setting(string $key, ?string $default = null): ?string
    {
        return TrialSetting::value($key, $default);
    }
}
