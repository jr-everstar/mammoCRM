<?php

use App\Models\Account;
use App\Models\AccountDocument;
use App\Models\Asset;
use App\Models\Opportunity;
use App\Models\TrialAgreement;
use App\Models\User;
use App\Services\TrialAgreements\PdfConverter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('lets staff manage assets while sales can only view availability', function () {
    $staff = User::where('email', 'staff@example.com')->firstOrFail();
    $sales = User::where('email', 'sales@example.com')->firstOrFail();

    $this->actingAs($sales)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertSee('Assets')
        ->assertSee('SEN-L-001');

    $this->actingAs($sales)
        ->get(route('assets.create'))
        ->assertForbidden();

    $this->actingAs($staff)
        ->post(route('assets.store'), [
            'type' => 'sensor',
            'side' => 'left',
            'serial_number' => 'LEFT-999',
            'model_name' => 'mammo care Left Sensor',
            'status' => 'available',
            'condition' => 'good',
            'location' => 'Central Office',
        ])
        ->assertRedirect(route('assets.index'));

    $this->assertDatabaseHas('assets', [
        'asset_tag' => 'SEN-L-0003',
        'type' => 'sensor',
        'side' => 'left',
    ]);

    $asset = Asset::where('serial_number', 'LEFT-999')->firstOrFail();

    $this->actingAs($staff)
        ->put(route('assets.update', $asset), [
            'asset_tag' => 'SHOULD-NOT-CHANGE',
            'type' => 'sensor',
            'side' => 'left',
            'serial_number' => 'LEFT-999-UPDATED',
            'model_name' => 'mammo care Left Sensor',
            'status' => 'available',
            'condition' => 'good',
            'location' => 'Central Office',
        ])
        ->assertRedirect(route('assets.index'));

    expect($asset->refresh()->asset_tag)->toBe('SEN-L-0003');
});

it('blocks trial agreement generation when required sensors are unavailable', function () {
    $sales = User::where('email', 'sales@example.com')->firstOrFail();
    $opportunity = Opportunity::where('opportunity_name', 'Harbour PLAN B New Deal')->firstOrFail();

    Asset::where('type', 'sensor')->where('side', 'right')->update(['status' => 'reserved']);

    $this->actingAs($sales)
        ->from(route('trial-agreements.create', $opportunity))
        ->post(route('trial-agreements.store'), trialPayload($opportunity))
        ->assertSessionHasErrors('assets');

    expect(TrialAgreement::count())->toBe(0);
});

it('renders the trial agreement generation page with auto assigned assets', function () {
    $sales = User::where('email', 'sales@example.com')->firstOrFail();
    $opportunity = Opportunity::where('opportunity_name', 'Harbour PLAN B New Deal')->firstOrFail();

    $this->actingAs($sales)
        ->get(route('trial-agreements.create', $opportunity))
        ->assertOk()
        ->assertSee('System Assigned Assets')
        ->assertSee('IPAD-001')
        ->assertSee('SEN-L-001')
        ->assertSee('SEN-R-001');
});

it('auto assigns available iPads and sensor pairs then reserves them', function () {
    $this->mock(PdfConverter::class, function ($mock) {
        $mock->shouldReceive('convert')->once()->andReturnUsing(function (string $docxPath, string $pdfPath): void {
            file_put_contents($pdfPath, '%PDF-1.4 trial agreement');
        });
    });

    $sales = User::where('email', 'sales@example.com')->firstOrFail();
    $opportunity = Opportunity::where('opportunity_name', 'Harbour PLAN B New Deal')->firstOrFail();
    $assetIds = autoAssignedAssetIds();

    $this->actingAs($sales)
        ->post(route('trial-agreements.store'), trialPayload($opportunity))
        ->assertRedirect(route('crm.show', ['opportunities', $opportunity->id]));

    $agreement = TrialAgreement::firstOrFail();

    expect($agreement->document_number)->toStartWith('TA-')
        ->and($agreement->generated_pdf_path)->not->toBeNull()
        ->and(Asset::whereIn('id', $assetIds)->where('status', 'reserved')->count())->toBe(count($assetIds));

    expect($agreement->assets()->where('type', 'ipad')->count())->toBe(1)
        ->and($agreement->assets()->where('type', 'sensor')->where('side', 'left')->count())->toBe(1)
        ->and($agreement->assets()->where('type', 'sensor')->where('side', 'right')->count())->toBe(1);

    $this->assertDatabaseHas('account_documents', [
        'account_id' => $opportunity->account_id,
        'type' => 'trial_agreement',
        'document_number' => $agreement->document_number,
        'status' => 'generated',
    ]);

    $this->actingAs($sales)
        ->get(route('crm.show', ['accounts', Account::firstOrFail()->id]))
        ->assertOk()
        ->assertSee('Trial Agreements 試用協議')
        ->assertSee($agreement->document_number);
});

it('generates a pdf through the html fallback when soffice is unavailable', function () {
    $sales = User::where('email', 'sales@example.com')->firstOrFail();
    $opportunity = Opportunity::where('opportunity_name', 'Harbour PLAN B New Deal')->firstOrFail();

    $this->actingAs($sales)
        ->post(route('trial-agreements.store'), trialPayload($opportunity))
        ->assertRedirect(route('crm.show', ['opportunities', $opportunity->id]));

    $agreement = TrialAgreement::firstOrFail();

    expect($agreement->generated_pdf_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($agreement->generated_pdf_path))->toBeTrue()
        ->and(Storage::disk('local')->get($agreement->generated_pdf_path))->toStartWith('%PDF');
});

it('blocks trial agreement generation when required ipads are unavailable', function () {
    $sales = User::where('email', 'sales@example.com')->firstOrFail();
    $opportunity = Opportunity::where('opportunity_name', 'Harbour PLAN B New Deal')->firstOrFail();

    Asset::where('type', 'ipad')->update(['status' => 'reserved']);

    $this->actingAs($sales)
        ->post(route('trial-agreements.store'), trialPayload($opportunity))
        ->assertSessionHasErrors('assets');

    expect(TrialAgreement::count())->toBe(0)
        ->and(AccountDocument::where('type', 'trial_agreement')->count())->toBe(0);
});

function trialPayload(Opportunity $opportunity): array
{
    return [
        'opportunity_id' => $opportunity->id,
        'effective_date' => now()->toDateString(),
        'trial_start_date' => now()->toDateString(),
        'trial_end_date' => now()->addDays(7)->toDateString(),
        'trial_location' => $opportunity->account->address,
        'client_coordinator_name' => $opportunity->account->contact_person_name,
        'client_coordinator_email' => $opportunity->account->contact_email,
        'client_coordinator_phone' => $opportunity->account->contact_phone,
        'delivery_method' => 'Courier',
        'return_method' => 'Courier pickup',
        'return_address' => 'EverStar Hong Kong Office',
        'trial_fee' => 'Waived',
        'security_deposit' => 'N/A',
    ];
}

function autoAssignedAssetIds(): array
{
    return array_merge(
        Asset::where('type', 'ipad')->orderBy('asset_tag')->limit(1)->pluck('id')->all(),
        Asset::where('type', 'sensor')->where('side', 'left')->orderBy('asset_tag')->limit(1)->pluck('id')->all(),
        Asset::where('type', 'sensor')->where('side', 'right')->orderBy('asset_tag')->limit(1)->pluck('id')->all(),
    );
}
