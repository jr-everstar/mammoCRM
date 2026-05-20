<?php

namespace App\Services\TrialAgreements;

use App\Models\Asset;
use App\Models\TrialAgreement;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class TrialAgreementDocumentGenerator
{
    public function __construct(private readonly PdfConverter $pdfConverter) {}

    public function generate(TrialAgreement $agreement): array
    {
        $agreement->loadMissing(['account', 'contact', 'salesPlan', 'salesUser', 'accountManager', 'assets']);

        $basePath = 'trial-agreements/'.$agreement->id;
        $docxPath = $basePath.'/'.$agreement->document_number.'.docx';
        $pdfPath = $basePath.'/'.$agreement->document_number.'.pdf';
        $absoluteDocxPath = Storage::disk('local')->path($docxPath);
        $absolutePdfPath = Storage::disk('local')->path($pdfPath);

        if (! is_dir(dirname($absoluteDocxPath))) {
            mkdir(dirname($absoluteDocxPath), 0775, true);
        }

        $template = new TemplateProcessor(resource_path('templates/trial_agreement_template.docx'));

        foreach ($this->values($agreement) as $key => $value) {
            $template->setValue($key, $this->clean($value));
        }

        $template->saveAs($absoluteDocxPath);
        $this->pdfConverter->convert($absoluteDocxPath, $absolutePdfPath);

        return [$docxPath, $pdfPath];
    }

    private function values(TrialAgreement $agreement): array
    {
        $account = $agreement->account;
        $contact = $agreement->contact;
        $assets = $agreement->assets;
        $ipads = $assets->where('type', 'ipad');
        $leftSensors = $assets->where('type', 'sensor')->where('side', 'left');
        $rightSensors = $assets->where('type', 'sensor')->where('side', 'right');
        $cablesAndChargers = $assets->whereIn('type', ['charger', 'cable']);
        $accessories = $assets->whereIn('type', ['case', 'accessory', 'other']);
        $sensorSetQuantity = min($leftSensors->count(), $rightSensors->count());

        return [
            'effective_date' => $agreement->effective_date?->format('Y-m-d'),
            'trial_start_date' => $agreement->trial_start_date?->format('Y-m-d'),
            'trial_end_date' => $agreement->trial_end_date?->format('Y-m-d'),
            'everstar_address' => $agreement->everstar_address,
            'client_company_name' => $account->company_name,
            'client_address' => $account->address,
            'everstar_am_name' => $agreement->accountManager?->name,
            'director_name' => $agreement->director_name,
            'client_signatory_name' => $agreement->client_coordinator_name ?: $contact?->name,
            'client_signatory_title' => $agreement->client_coordinator_title ?: $contact?->title,
            'slot_01' => $account->company_registration_number,
            'slot_02' => (string) $ipads->count(),
            'slot_03' => (string) $sensorSetQuantity,
            'slot_04' => '1',
            'slot_05' => '1',
            'slot_06' => $agreement->trial_fee,
            'slot_07' => $agreement->security_deposit,
            'slot_08' => 'N/A',
            'slot_09' => $account->company_name,
            'slot_10' => $account->company_registration_number,
            'slot_11' => $agreement->trial_location,
            'slot_12' => $agreement->trial_start_date?->format('Y-m-d'),
            'slot_13' => $agreement->trial_end_date?->format('Y-m-d'),
            'slot_14' => $agreement->client_coordinator_name,
            'slot_15' => $agreement->accountManager?->name,
            'slot_16' => $agreement->delivery_method,
            'slot_17' => $agreement->return_method,
            'slot_18' => $agreement->return_address,
            'slot_19' => $agreement->trial_fee,
            'slot_20' => $agreement->security_deposit,
            'slot_21' => $agreement->special_conditions ?: 'N/A',
            'slot_22' => $agreement->accountManager?->name,
            'slot_23' => 'Account Manager',
            'slot_24' => $agreement->accountManager?->email,
            'slot_25' => '',
            'slot_26' => $agreement->client_coordinator_name ?: $contact?->name,
            'slot_27' => $agreement->client_coordinator_title ?: $contact?->title,
            'slot_28' => $agreement->client_coordinator_email ?: $contact?->email,
            'slot_29' => $agreement->client_coordinator_phone ?: $contact?->phone,
            'slot_30' => $this->models($ipads),
            'slot_31' => $this->assetTags($ipads),
            'slot_32' => (string) $ipads->count(),
            'slot_33' => $this->conditions($ipads),
            'slot_34' => '',
            'slot_35' => $this->models($leftSensors),
            'slot_36' => $this->assetTags($leftSensors),
            'slot_37' => (string) $leftSensors->count(),
            'slot_38' => $this->conditions($leftSensors),
            'slot_39' => '',
            'slot_40' => $this->models($rightSensors),
            'slot_41' => $this->assetTags($rightSensors),
            'slot_42' => (string) $rightSensors->count(),
            'slot_43' => $this->conditions($rightSensors),
            'slot_44' => '',
            'slot_45' => $this->models($cablesAndChargers),
            'slot_46' => $this->assetTags($cablesAndChargers),
            'slot_47' => (string) $cablesAndChargers->count(),
            'slot_48' => $this->conditions($cablesAndChargers),
            'slot_49' => '',
            'slot_50' => $this->models($accessories),
            'slot_51' => $this->assetTags($accessories),
            'slot_52' => (string) $accessories->count(),
            'slot_53' => $this->conditions($accessories),
            'slot_54' => '',
            'slot_55' => '',
            'slot_56' => '',
            'slot_57' => '',
            'slot_58' => $agreement->trial_location,
            'slot_59' => $agreement->client_coordinator_name,
        ];
    }

    private function models($assets): string
    {
        return $assets->map(fn (Asset $asset) => $asset->model_name ?: $asset->type)->filter()->implode('; ') ?: 'N/A';
    }

    private function assetTags($assets): string
    {
        return $assets->map(fn (Asset $asset) => trim($asset->serial_number.' / '.$asset->asset_tag, ' /'))->filter()->implode('; ') ?: 'N/A';
    }

    private function conditions($assets): string
    {
        return $assets->pluck('condition')->filter()->unique()->implode('; ') ?: 'good';
    }

    private function clean(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_COMPAT, 'UTF-8');
    }
}
