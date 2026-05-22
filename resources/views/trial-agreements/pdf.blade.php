@php
    $account = $agreement->account;
    $contact = $agreement->contact;
    $assets = $agreement->assets;
    $ipads = $assets->where('type', 'ipad');
    $leftSensors = $assets->where('type', 'sensor')->where('side', 'left');
    $rightSensors = $assets->where('type', 'sensor')->where('side', 'right');
    $assetTags = fn ($items) => $items->map(fn ($asset) => trim(($asset->serial_number ?: '').' / '.$asset->asset_tag, ' /'))->implode('; ') ?: 'N/A';
    $assetModels = fn ($items) => $items->map(fn ($asset) => $asset->model_name ?: str($asset->type)->headline())->implode('; ') ?: 'N/A';
    $conditions = fn ($items) => $items->pluck('condition')->filter()->unique()->implode('; ') ?: 'good';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22mm 18mm; }
        body { color: #111827; font-family: "DejaVu Sans", sans-serif; font-size: 11px; line-height: 1.42; }
        h1 { font-size: 21px; margin: 0 0 3px; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 14px; margin: 18px 0 8px; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 12px 0 5px; }
        p { margin: 0 0 7px; }
        table { border-collapse: collapse; margin: 8px 0 12px; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 7px; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; text-align: left; }
        .subtitle { color: #475569; font-size: 12px; margin-bottom: 12px; }
        .muted { color: #64748b; }
        .signature { margin-top: 22px; page-break-inside: avoid; }
        .line { border-bottom: 1px solid #111827; display: inline-block; min-width: 220px; padding-top: 18px; }
        .page-break { page-break-before: always; }
        ul { margin: 4px 0 10px 18px; padding: 0; }
        li { margin-bottom: 4px; }
    </style>
</head>
<body>
    <h1>mammo care Trial Program Agreement</h1>
    <div class="subtitle">7-Day Trial Program | Device Loan | Participant Consent | Signature Required</div>

    <p>This mammo care Trial Program Agreement for Hong Kong is made on <strong>{{ $agreement->effective_date?->format('Y-m-d') }}</strong> by and between:</p>
    <p><strong>Service Provider:</strong> EverStar International Management Limited, with registered office at {{ $agreement->everstar_address ?: 'Hong Kong' }}.</p>
    <p><strong>Client:</strong> {{ $account->company_name }}, Business Registration / Company Registration No. {{ $account->company_registration_number }}, with registered office / business address at {{ $account->address ?: 'N/A' }}.</p>

    <table>
        <tr><th>Key Item</th><th>Summary</th></tr>
        <tr><td>Trial Program</td><td>7 calendar days unless extended in writing by EverStar</td></tr>
        <tr><td>Permitted Use</td><td>Internal evaluation and trial workflow only; no commercial customer charging unless approved in writing</td></tr>
        <tr><td>Device Ownership</td><td>All devices, sensors, software access, materials, and documentation remain property of EverStar or its licensors</td></tr>
        <tr><td>Governing Law</td><td>Hong Kong law; non-exclusive jurisdiction of Hong Kong courts</td></tr>
        <tr><td>Signature Requirement</td><td>EverStar Account Manager, EverStar Director, and Client authorised signatory; company chop required for EverStar and Client</td></tr>
    </table>

    <h2>1. Purpose and Trial Period</h2>
    <p>EverStar provides the Client with access to the mammo care trial program solely for a seven (7) day pilot test and evaluation of mammo care services, equipment, app workflow, report workflow, operational process, and commercial suitability.</p>
    <p>The trial period shall commence on <strong>{{ $agreement->trial_start_date?->format('Y-m-d') }}</strong> and end on <strong>{{ $agreement->trial_end_date?->format('Y-m-d') }}</strong>, unless extended by written agreement signed by both Parties.</p>
    <p>This Agreement does not create any purchase agreement, reseller agreement, agency agreement, medical service agreement, partnership, franchise, joint venture, exclusive cooperation, or long-term commercial arrangement.</p>

    <h2>2. Equipment on Loan and Trial Access</h2>
    <p>EverStar may provide the Client with the equipment, software access, and trial materials listed below. Trial materials remain the property of EverStar or its licensors at all times.</p>
    <table>
        <tr><th>Item</th><th>Description</th><th>Quantity</th><th>Ownership</th></tr>
        <tr><td>Managed iPad</td><td>MDM-managed iPad configured for mammo care trial use</td><td>{{ $ipads->count() }}</td><td>EverStar</td></tr>
        <tr><td>Sensor Set</td><td>mammo care sensor set, one left sensor plus one right sensor</td><td>{{ min($leftSensors->count(), $rightSensors->count()) }}</td><td>EverStar / licensor</td></tr>
        <tr><td>mammo care App Access</td><td>Trial account / app access</td><td>1</td><td>EverStar / licensor</td></tr>
        <tr><td>Trial Materials</td><td>Training, workflow, report sample, documents, scripts, and operational materials</td><td>1</td><td>EverStar / licensor</td></tr>
    </table>

    <h2>3. Client Responsibilities</h2>
    <ul>
        <li>Use the Trial Materials with reasonable care and only for trial and lawful purposes.</li>
        <li>Keep Trial Materials secure and protected from theft, loss, liquid, excessive heat, impact, unauthorised access, misuse, and third-party use.</li>
        <li>Not remove, alter, cover, or damage serial numbers, asset tags, labels, software profiles, security controls, or device identifiers.</li>
        <li>Not sell, transfer, pledge, charge, sublease, lend, assign, repair, factory reset, jailbreak, unlock, erase, or tamper with any Trial Materials.</li>
        <li>Immediately report any loss, theft, damage, malfunction, complaint, data incident, or suspected breach to EverStar.</li>
    </ul>

    <h2>4. MDM, Data, Medical, Privacy, Confidentiality, and Return Terms</h2>
    <p>The Client acknowledges that the iPad may be enrolled in EverStar's mobile device management system and shall not remove, disable, tamper with, bypass, or attempt to bypass device management controls.</p>
    <p>The trial service is provided for evaluation, screening workflow demonstration, and operational assessment only. It is not medical diagnosis, medical advice, treatment recommendation, disease confirmation, emergency service, or a substitute for consultation with a registered medical practitioner.</p>
    <p>Each Party shall comply with the Personal Data (Privacy) Ordinance (Cap. 486) of the Laws of Hong Kong and all applicable privacy, personal data, cybersecurity, confidentiality, and consent requirements.</p>
    <p>The Client shall keep confidential all information relating to EverStar, mammo care, the trial program, equipment, app screens, system access, pricing, commercial terms, workflow, reports, AI analysis process, documentation, training materials, know-how, trade secrets, and all non-public information disclosed during the trial.</p>
    <p>Upon expiry or termination, the Client shall immediately stop using the trial service, return all Trial Materials, stop using confidential information, and cooperate with EverStar's return, reset, wipe, and deactivation process.</p>

    <h2>5. Fixed Charges for Loss, Theft, Damage, Tampering, and Non-return</h2>
    <table>
        <tr><th>Item / Event</th><th>Fixed Charge / Liability</th></tr>
        <tr><td>Managed iPad lost, stolen, not returned, materially damaged, or MDM tampered</td><td>HKD 5,000 per unit, plus recovery, courier, administrative, and other reasonable costs</td></tr>
        <tr><td>Sensor Set lost, stolen, not returned, or materially damaged</td><td>HKD 500 per set, plus recovery, courier, administrative, and other reasonable costs</td></tr>
        <tr><td>Accessories, cables, chargers, cases, packaging, or other materials</td><td>Actual replacement or repair cost</td></tr>
        <tr><td>Repairable damage</td><td>Reasonable repair cost including parts and labour, capped at replacement cost unless otherwise agreed</td></tr>
    </table>

    <h2>Schedule 1. Trial Details</h2>
    <table>
        <tr><th>Item</th><th>Details</th></tr>
        <tr><td>Client Company Name</td><td>{{ $account->company_name }}</td></tr>
        <tr><td>Company Registration Number / Business Registration Number</td><td>{{ $account->company_registration_number }}</td></tr>
        <tr><td>Trial Location</td><td>{{ $agreement->trial_location ?: 'N/A' }}</td></tr>
        <tr><td>Trial Start Date</td><td>{{ $agreement->trial_start_date?->format('Y-m-d') }}</td></tr>
        <tr><td>Trial End Date</td><td>{{ $agreement->trial_end_date?->format('Y-m-d') }}</td></tr>
        <tr><td>Trial Coordinator - Client</td><td>{{ $agreement->client_coordinator_name ?: $contact?->name ?: 'N/A' }}</td></tr>
        <tr><td>Account Manager - EverStar</td><td>{{ $agreement->accountManager?->name ?: 'N/A' }}</td></tr>
        <tr><td>Delivery Method</td><td>{{ $agreement->delivery_method ?: 'N/A' }}</td></tr>
        <tr><td>Return Method</td><td>{{ $agreement->return_method ?: 'N/A' }}</td></tr>
        <tr><td>Return Address</td><td>{{ $agreement->return_address ?: 'N/A' }}</td></tr>
        <tr><td>Trial Fee</td><td>{{ $agreement->trial_fee ?: 'Waived' }}</td></tr>
        <tr><td>Security Deposit</td><td>{{ $agreement->security_deposit ?: 'N/A' }}</td></tr>
        <tr><td>Special Conditions</td><td>{{ $agreement->special_conditions ?: 'N/A' }}</td></tr>
    </table>

    <h2>Schedule 2. Trial Equipment Handover and Return Record</h2>
    <table>
        <tr><th>Item</th><th>Description / Model</th><th>Serial No. / Asset ID</th><th>Qty</th><th>Condition at Handover</th><th>Condition at Return</th></tr>
        <tr><td>Managed iPad</td><td>{{ $assetModels($ipads) }}</td><td>{{ $assetTags($ipads) }}</td><td>{{ $ipads->count() }}</td><td>{{ $conditions($ipads) }}</td><td></td></tr>
        <tr><td>Sensor Set L</td><td>{{ $assetModels($leftSensors) }}</td><td>{{ $assetTags($leftSensors) }}</td><td>{{ $leftSensors->count() }}</td><td>{{ $conditions($leftSensors) }}</td><td></td></tr>
        <tr><td>Sensor Set R</td><td>{{ $assetModels($rightSensors) }}</td><td>{{ $assetTags($rightSensors) }}</td><td>{{ $rightSensors->count() }}</td><td>{{ $conditions($rightSensors) }}</td><td></td></tr>
        <tr><td>Cable / Charger</td><td>N/A</td><td>N/A</td><td>0</td><td></td><td></td></tr>
        <tr><td>Other Accessories</td><td>N/A</td><td>N/A</td><td>0</td><td></td><td></td></tr>
    </table>

    <div class="page-break"></div>
    <h2>Schedule 3. Participant Consent and Acknowledgement Form</h2>
    <table>
        <tr><th>Item</th><th>Details</th></tr>
        <tr><td>Participant Name</td><td></td></tr>
        <tr><td>Phone / Email</td><td></td></tr>
        <tr><td>Date of Participation</td><td></td></tr>
        <tr><td>Client / Trial Location</td><td>{{ $agreement->trial_location ?: '' }}</td></tr>
        <tr><td>Handled by</td><td>{{ $agreement->client_coordinator_name ?: '' }}</td></tr>
    </table>
    <p>By signing this form, the participant acknowledges voluntary participation, the non-diagnostic nature of the trial, consent to collection and use of trial-related personal data for trial purposes, and the right to ask questions before participation.</p>
    <p>Participant Name: <span class="line"></span></p>
    <p>Participant Signature: <span class="line"></span></p>
    <p>Date: <span class="line"></span></p>

    <h2>Execution. Signatures</h2>
    <div class="signature">
        <h3>Signed for and on behalf of EverStar International Management Limited - Account Manager</h3>
        <p>Name: {{ $agreement->accountManager?->name ?: '' }}</p>
        <p>Title: Account Manager</p>
        <p>Signature: <span class="line"></span></p>
        <p>Date: <span class="line"></span></p>
    </div>
    <div class="signature">
        <h3>Signed for and on behalf of EverStar International Management Limited - Director Level</h3>
        <p>Name: {{ $agreement->director_name ?: '' }}</p>
        <p>Title: Director</p>
        <p>Signature: <span class="line"></span></p>
        <p>Date: <span class="line"></span></p>
        <p>EverStar Company Chop: <span class="line"></span></p>
    </div>
    <div class="signature">
        <h3>Signed for and on behalf of {{ $account->company_name }}</h3>
        <p>Name: {{ $agreement->client_coordinator_name ?: $contact?->name ?: '' }}</p>
        <p>Title: {{ $agreement->client_coordinator_title ?: $contact?->title ?: '' }}</p>
        <p>Company: {{ $account->company_name }}</p>
        <p>Signature: <span class="line"></span></p>
        <p>Company Chop: <span class="line"></span></p>
        <p>Date: <span class="line"></span></p>
    </div>
</body>
</html>
