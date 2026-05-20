<?php

use App\Models\Account;
use App\Models\CommissionRule;
use App\Models\CommissionRun;
use App\Models\Contact;
use App\Models\CrmComment;
use App\Models\Deal;
use App\Models\HighPlanAcceleratorRule;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Models\SalesPlan;
use App\Models\User;
use App\Services\Commission\CommissionCalculator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('enforces unique account registration numbers', function () {
    $sales = User::where('email', 'sales@example.com')->first();

    Account::create([
        'company_name' => 'Duplicate Clinic',
        'company_registration_number' => 'UNIQUE-001',
        'account_manager_id' => $sales->id,
        'created_by' => $sales->id,
        'status' => 'prospect',
    ]);

    expect(fn () => Account::create([
        'company_name' => 'Duplicate Clinic 2',
        'company_registration_number' => 'UNIQUE-001',
        'account_manager_id' => $sales->id,
        'created_by' => $sales->id,
        'status' => 'prospect',
    ]))->toThrow(QueryException::class);
});

it('blocks inactive users from authenticating', function () {
    Role::firstOrCreate(['name' => 'sales']);
    $user = User::create([
        'name' => 'Inactive Sales',
        'email' => 'inactive@example.com',
        'password' => Hash::make('password'),
        'status' => 'inactive',
    ]);
    $user->assignRole('sales');

    $this->post('/login', ['email' => 'inactive@example.com', 'password' => 'password'])
        ->assertSessionHasErrors();
});

it('renders lead create form without edit state errors', function () {
    $sales = User::where('email', 'sales@example.com')->first();

    $this->actingAs($sales)
        ->get(route('crm.create', 'leads'))
        ->assertOk()
        ->assertSee('新增商機 (Leads)');
});

it('renders account edit form without edit state errors', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();

    $this->actingAs($sales)
        ->get(route('crm.edit', ['accounts', $account->id]))
        ->assertOk()
        ->assertSee('編輯客戶 / 商戶帳戶');
});

it('renders salesforce-style lead and opportunity workspaces', function () {
    $sales = User::where('email', 'sales@example.com')->first();

    $this->actingAs($sales)
        ->get(route('crm.index', 'leads'))
        ->assertOk()
        ->assertSee('像 Salesforce 一樣先收集商機 Leads')
        ->assertSee('轉換 / 查看');

    $this->actingAs($sales)
        ->get(route('crm.index', 'opportunities'))
        ->assertOk()
        ->assertSee('Pipeline 金額')
        ->assertSee('打開看板');

    $this->actingAs($sales)
        ->get(route('kanban'))
        ->assertOk()
        ->assertSee('像 Salesforce Pipeline')
        ->assertSee('階段等於下一步');
});

it('renders contacts object and account 360 related history', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();

    $this->actingAs($sales)
        ->get(route('crm.index', 'contacts'))
        ->assertOk()
        ->assertSee('聯絡人 Contacts')
        ->assertSee('Winnie Chan');

    $this->actingAs($sales)
        ->get(route('crm.create', ['module' => 'contacts', 'account_id' => $account->id]))
        ->assertOk()
        ->assertSee('新增聯絡人 Contacts');

    $this->actingAs($sales)
        ->get(route('crm.show', ['accounts', $account->id]))
        ->assertOk()
        ->assertSee('客戶歷史總覽')
        ->assertSee('Contacts')
        ->assertSee('商機 (Leads)')
        ->assertSee('機會 (OP)')
        ->assertSee('Quotations 報價')
        ->assertSee('Contracts 合約')
        ->assertSee('Invoices 發票');

    expect(Contact::where('account_id', $account->id)->count())->toBeGreaterThan(0);
});

it('creates a primary contact from manual account contact fields', function () {
    $admin = User::where('email', 'admin@example.com')->first();
    $sales = User::where('email', 'sales@example.com')->first();

    $this->actingAs($admin)->post(route('crm.store', 'accounts'), [
        'company_name' => 'Manual Contact Clinic',
        'company_registration_number' => 'MANUAL-CONTACT-001',
        'business_type' => 'Clinic',
        'contact_person_name' => 'Maggie Wong',
        'contact_person_title' => 'Practice Manager',
        'contact_phone' => '+852 3000 3333',
        'contact_email' => 'maggie@example.com',
        'address' => 'Causeway Bay, Hong Kong',
        'account_manager_id' => $sales->id,
        'status' => 'prospect',
        'notes' => 'Created manually by admin.',
    ])->assertRedirect();

    $account = Account::where('company_registration_number', 'MANUAL-CONTACT-001')->first();
    $contact = Contact::where('account_id', $account->id)->where('email', 'maggie@example.com')->first();

    expect($contact)->not->toBeNull()
        ->and($contact->name)->toBe('Maggie Wong')
        ->and($contact->title)->toBe('Practice Manager')
        ->and($contact->phone)->toBe('+852 3000 3333')
        ->and($contact->whatsapp)->toBe('+852 3000 3333')
        ->and($contact->is_primary)->toBeTrue();
});

it('updates the primary contact when manual account contact fields change', function () {
    $admin = User::where('email', 'admin@example.com')->first();
    $sales = User::where('email', 'sales@example.com')->first();

    $account = Account::create([
        'company_name' => 'Editable Contact Clinic',
        'company_registration_number' => 'EDITABLE-CONTACT-001',
        'contact_person_name' => 'Old Contact',
        'contact_person_title' => 'Manager',
        'contact_phone' => '+852 3000 4444',
        'contact_email' => 'old-contact@example.com',
        'account_manager_id' => $sales->id,
        'created_by' => $admin->id,
        'status' => 'prospect',
    ]);

    Contact::create([
        'account_id' => $account->id,
        'name' => 'Old Contact',
        'title' => 'Manager',
        'phone' => '+852 3000 4444',
        'email' => 'old-contact@example.com',
        'whatsapp' => '+852 3000 4444',
        'is_primary' => true,
        'status' => 'active',
    ]);

    $this->actingAs($admin)->put(route('crm.update', ['accounts', $account->id]), [
        'company_name' => 'Editable Contact Clinic',
        'company_registration_number' => 'EDITABLE-CONTACT-001',
        'business_type' => 'Clinic',
        'contact_person_name' => 'New Contact',
        'contact_person_title' => 'Director',
        'contact_phone' => '+852 3000 5555',
        'contact_email' => 'new-contact@example.com',
        'address' => 'Central, Hong Kong',
        'account_manager_id' => $sales->id,
        'status' => 'active',
        'notes' => 'Contact changed during account edit.',
    ])->assertRedirect();

    expect(Contact::where('account_id', $account->id)->count())->toBe(1);

    $contact = Contact::where('account_id', $account->id)->first();

    expect($contact->name)->toBe('New Contact')
        ->and($contact->title)->toBe('Director')
        ->and($contact->phone)->toBe('+852 3000 5555')
        ->and($contact->email)->toBe('new-contact@example.com')
        ->and($contact->is_primary)->toBeTrue();
});

it('marks the first contact on an account as primary', function () {
    $admin = User::where('email', 'admin@example.com')->first();
    $sales = User::where('email', 'sales@example.com')->first();

    $account = Account::create([
        'company_name' => 'First Contact Clinic',
        'company_registration_number' => 'FIRST-CONTACT-001',
        'account_manager_id' => $sales->id,
        'created_by' => $admin->id,
        'status' => 'prospect',
    ]);

    $this->actingAs($admin)->post(route('crm.store', 'contacts'), [
        'account_id' => $account->id,
        'name' => 'First Contact',
        'title' => 'Manager',
        'phone' => '+852 3000 6666',
        'email' => 'first-contact@example.com',
        'status' => 'active',
    ])->assertRedirect();

    expect(Contact::where('account_id', $account->id)->where('email', 'first-contact@example.com')->value('is_primary'))
        ->toBeTrue();
});

it('keeps only one primary contact per account', function () {
    $admin = User::where('email', 'admin@example.com')->first();
    $account = Account::first();
    $firstContact = $account->contacts()->where('is_primary', true)->first();

    $this->actingAs($admin)->post(route('crm.store', 'contacts'), [
        'account_id' => $account->id,
        'name' => 'Replacement Primary',
        'title' => 'Director',
        'phone' => '+852 3000 7777',
        'email' => 'replacement-primary@example.com',
        'is_primary' => 1,
        'status' => 'active',
    ])->assertRedirect();

    $replacement = Contact::where('account_id', $account->id)->where('email', 'replacement-primary@example.com')->first();

    expect($replacement->is_primary)->toBeTrue()
        ->and($firstContact->refresh()->is_primary)->toBeFalse()
        ->and(Contact::where('account_id', $account->id)->where('is_primary', true)->count())->toBe(1);
});

it('prevents sales users from seeing another sales users account', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $other = User::create(['name' => 'Other Sales', 'email' => 'other@example.com', 'password' => 'password', 'status' => 'active']);
    $other->assignRole('sales');

    $account = Account::create([
        'company_name' => 'Private Clinic',
        'company_registration_number' => 'PRIVATE-001',
        'account_manager_id' => $other->id,
        'created_by' => $other->id,
        'status' => 'prospect',
    ]);

    $this->actingAs($sales)->get(route('crm.show', ['accounts', $account->id]))->assertNotFound();
});

it('lets staff manage crm records without system settings or user management access', function () {
    $staff = User::where('email', 'staff@example.com')->first();
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    $lead = Lead::where('assigned_sales_id', $sales->id)->first();
    $opportunity = Opportunity::where('assigned_sales_id', $sales->id)->first();

    expect($staff->canManageCrm())->toBeTrue()
        ->and($staff->isAdmin())->toBeFalse();

    $this->actingAs($staff)->get(route('crm.show', ['accounts', $account->id]))->assertOk();
    $this->actingAs($staff)->get(route('crm.index', 'contacts'))->assertOk()->assertSee('Winnie Chan');
    $this->actingAs($staff)->get(route('crm.show', ['leads', $lead->id]))->assertOk();
    $this->actingAs($staff)->get(route('crm.show', ['opportunities', $opportunity->id]))->assertOk();
    $this->actingAs($staff)->get(route('kanban'))->assertOk()->assertSee($opportunity->opportunity_name);

    $this->actingAs($staff)->get(route('config.index', 'sales-plans'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
});

it('converts a lead by linking an existing account and creating an opportunity', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    $planB = SalesPlan::where('plan_name', 'PLAN B')->first();
    $lead = Lead::create([
        'lead_name' => 'Existing Merchant Lead',
        'company_name' => $account->company_name,
        'contact_person' => 'Lead Primary Contact',
        'contact_phone' => '+852 3999 0000',
        'contact_email' => 'lead-primary@example.com',
        'assigned_sales_id' => $sales->id,
        'created_by' => $sales->id,
        'status' => 'New',
        'notes' => 'Primary contact should be created during conversion.',
    ]);

    $this->actingAs($sales)->post(route('leads.convert', $lead), [
        'company_registration_number' => $account->company_registration_number,
        'opportunity_name' => 'Existing Merchant Deal',
        'sales_plan_id' => $planB->id,
    ])->assertRedirect();

    $lead->refresh();
    $opportunity = Opportunity::where('opportunity_name', 'Existing Merchant Deal')->first();

    expect($lead->status)->toBe('Converted')
        ->and($lead->converted_account_id)->toBe($account->id)
        ->and($opportunity)->not->toBeNull()
        ->and((float) $opportunity->estimated_deal_amount)->toBe((float) $planB->selling_price);

    expect(Contact::where('account_id', $account->id)->where('email', 'lead-primary@example.com')->first())
        ->not->toBeNull()
        ->name->toBe('Lead Primary Contact')
        ->phone->toBe('+852 3999 0000')
        ->is_primary->toBeTrue();
});

it('updates an existing contact when converting a lead with the same contact email', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    $planB = SalesPlan::where('plan_name', 'PLAN B')->first();

    Contact::create([
        'account_id' => $account->id,
        'name' => 'Old Lead Contact',
        'phone' => '+852 3888 0000',
        'email' => 'existing-lead@example.com',
        'is_primary' => false,
        'status' => 'active',
    ]);

    $lead = Lead::create([
        'lead_name' => 'Existing Contact Lead',
        'company_name' => $account->company_name,
        'contact_person' => 'Updated Lead Contact',
        'contact_phone' => '+852 3999 1111',
        'contact_email' => 'existing-lead@example.com',
        'assigned_sales_id' => $sales->id,
        'created_by' => $sales->id,
        'status' => 'New',
    ]);

    $this->actingAs($sales)->post(route('leads.convert', $lead), [
        'company_registration_number' => $account->company_registration_number,
        'opportunity_name' => 'Existing Contact Deal',
        'sales_plan_id' => $planB->id,
    ])->assertRedirect();

    expect(Contact::where('account_id', $account->id)->where('email', 'existing-lead@example.com')->count())->toBe(1);

    $contact = Contact::where('account_id', $account->id)->where('email', 'existing-lead@example.com')->first();

    expect($contact->name)->toBe('Updated Lead Contact')
        ->and($contact->phone)->toBe('+852 3999 1111')
        ->and($contact->is_primary)->toBeTrue();
});

it('creates opportunities using the selected plan fixed price', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    $planC = SalesPlan::where('plan_name', 'PLAN C')->first();

    $this->actingAs($sales)->post(route('crm.store', 'opportunities'), [
        'account_id' => $account->id,
        'opportunity_name' => 'Fixed Price PLAN C OP',
        'sales_plan_id' => $planC->id,
        'probability' => 50,
        'expected_close_date' => now()->addMonth()->toDateString(),
        'stage' => 'Proposal',
    ])->assertRedirect();

    $opportunity = Opportunity::where('opportunity_name', 'Fixed Price PLAN C OP')->first();

    expect($opportunity)->not->toBeNull()
        ->and((float) $opportunity->estimated_deal_amount)->toBe((float) $planC->selling_price)
        ->and($opportunity->probability)->toBe(60);
});

it('updates opportunity probability from the configured stage rule', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $opportunity = Opportunity::where('stage', 'Done Deal')->first();
    $opportunity->update(['stage' => 'Proposal', 'probability' => 60]);

    $this->actingAs($sales)->put(route('crm.update', ['opportunities', $opportunity->id]), [
        'account_id' => $opportunity->account_id,
        'opportunity_name' => $opportunity->opportunity_name,
        'sales_plan_id' => $opportunity->sales_plan_id,
        'expected_close_date' => optional($opportunity->expected_close_date)->toDateString(),
        'stage' => 'Negotiation',
        'notes' => $opportunity->notes,
    ])->assertRedirect();

    expect($opportunity->refresh()->probability)->toBe(80);
});

it('lets users save notes and comments on leads and opportunities', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $admin = User::where('email', 'admin@example.com')->first();
    $lead = Lead::where('assigned_sales_id', $sales->id)->first();
    $opportunity = Opportunity::where('assigned_sales_id', $sales->id)->first();

    $this->actingAs($sales)
        ->get(route('crm.show', ['leads', $lead->id]))
        ->assertOk()
        ->assertSee('備註')
        ->assertSee('Comment');

    $this->actingAs($sales)
        ->post(route('crm.remarks', ['leads', $lead->id]), [
            'notes' => 'Customer asked for WhatsApp follow-up next week.',
        ])
        ->assertRedirect();

    expect($lead->refresh()->notes)->toBe('Customer asked for WhatsApp follow-up next week.');

    $this->actingAs($admin)
        ->post(route('crm.comments.store', ['leads', $lead->id]), [
            'body' => 'Admin confirmed discount approval is required.',
        ])
        ->assertRedirect();

    expect(CrmComment::whereMorphedTo('commentable', $lead)->where('user_id', $admin->id)->value('body'))
        ->toBe('Admin confirmed discount approval is required.');

    $this->actingAs($sales)
        ->get(route('crm.show', ['opportunities', $opportunity->id]))
        ->assertOk()
        ->assertSee('備註')
        ->assertSee('Comment');

    $this->actingAs($sales)
        ->post(route('crm.remarks', ['opportunities', $opportunity->id]), [
            'notes' => 'Clinic wants updated payment terms before signing.',
        ])
        ->assertRedirect();

    $this->actingAs($sales)
        ->post(route('crm.comments.store', ['opportunities', $opportunity->id]), [
            'body' => 'Sales replied with revised contract timing.',
        ])
        ->assertRedirect();

    expect($opportunity->refresh()->notes)->toBe('Clinic wants updated payment terms before signing.')
        ->and(CrmComment::whereMorphedTo('commentable', $opportunity)->where('user_id', $sales->id)->value('body'))
        ->toBe('Sales replied with revised contract timing.');
});

it('creates sales plans with their done deal commission rule', function () {
    $admin = User::where('email', 'admin@example.com')->first();

    $this->actingAs($admin)->post(route('config.store', 'sales-plans'), [
        'plan_name' => 'PLAN E',
        'selling_price' => 500000,
        'report_commitment' => 2000,
        'average_cost_per_report' => 220,
        'included_ipad_quantity' => 6,
        'included_sensor_set_quantity' => 12,
        'new_deal_commission' => 50000,
        'monthly_tier_count_percentage' => 100,
        'can_trigger_monthly_tier' => 1,
        'hpa_eligible' => 1,
        'display_order' => 6,
        'is_active' => 1,
    ])->assertRedirect();

    $plan = SalesPlan::where('plan_name', 'PLAN E')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->plan_code)->toStartWith('PLAN-')
        ->and((float) CommissionRule::where('sales_plan_id', $plan->id)->where('key', 'new_deal_commission_'.$plan->plan_code)->value('value'))->toBe(50000.0);
});

it('shows sales plan tier and hpa settings as configurable business rules', function () {
    $admin = User::where('email', 'admin@example.com')->first();

    expect(SalesPlan::whereIn('plan_code', ['BETA', 'A', 'B', 'C', 'D'])->exists())->toBeFalse()
        ->and(SalesPlan::where('plan_name', 'PLAN A')->value('plan_code'))->toBe('PLAN-0002')
        ->and(SalesPlan::where('plan_name', 'PLAN D')->value('plan_code'))->toBe('PLAN-0005');

    $this->actingAs($admin)
        ->get(route('config.index', 'sales-plans'))
        ->assertOk()
        ->assertSee('Monthly Tier 是否計入及是否觸發，由每個 Plan 決定。')
        ->assertSee('下方只顯示每個 plan 的關鍵資訊')
        ->assertSee('HPA 計入')
        ->assertDontSee('HPA Level')
        ->assertSee('Plan Code 會由系統自動產生')
        ->assertDontSee('name="plan_code"', false)
        ->assertSee('新增銷售方案');

    $this->actingAs($admin)
        ->get(route('config.index', 'high-accelerators'))
        ->assertOk()
        ->assertSee('HPA 是否計入由 Sales Plan 的「計入 HPA」決定')
        ->assertSee('新增 HPA 規則')
        ->assertSee('PLAN C QTY')
        ->assertSee('PLAN D QTY')
        ->assertDontSee('最少 PLAN C+')
        ->assertDontSee('最少 PLAN D+');
});

it('lets admin create and delete dynamic hpa quantity bonus rules', function () {
    $admin = User::where('email', 'admin@example.com')->first();

    $this->actingAs($admin)->post(route('config.store', 'high-accelerators'), [
        'name' => '5 HPA opt-in plan deals',
        'hpa_plan_quantities' => [
            SalesPlan::where('plan_name', 'PLAN C')->value('id') => 2,
            SalesPlan::where('plan_name', 'PLAN D')->value('id') => 3,
        ],
        'bonus' => 18000,
        'priority' => 5,
        'is_active' => 1,
    ])->assertRedirect();

    $rule = HighPlanAcceleratorRule::where('name', '5 HPA opt-in plan deals')->first();

    expect($rule)->not->toBeNull()
        ->and($rule->code)->toStartWith('HPA-')
        ->and($rule->min_hpa_eligible_deals)->toBe(5)
        ->and($rule->salesPlanRequirements()->where('plan_name', 'PLAN C')->first()->pivot->required_quantity)->toBe(2)
        ->and($rule->salesPlanRequirements()->where('plan_name', 'PLAN D')->first()->pivot->required_quantity)->toBe(3)
        ->and((float) $rule->bonus)->toBe(18000.0);

    $this->actingAs($admin)
        ->delete(route('config.destroy', ['high-accelerators', $rule->id]))
        ->assertRedirect();

    expect(HighPlanAcceleratorRule::whereKey($rule->id)->exists())->toBeFalse();
});

it('creates a deal when a kanban move reaches done deal and requires lost reason', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $opportunity = Opportunity::where('stage', 'Done Deal')->first();
    $opportunity->update(['stage' => 'Proposal']);

    $this->actingAs($sales)->postJson(route('kanban.move', $opportunity), ['stage' => 'Lost'])
        ->assertUnprocessable();

    $this->actingAs($sales)->postJson(route('kanban.move', $opportunity), [
        'stage' => 'Done Deal',
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'deal_type' => 'new_deal',
    ])->assertOk();

    $deal = Deal::where('opportunity_id', $opportunity->id)->first();

    expect($deal)->not->toBeNull()
        ->and((float) $deal->deal_amount)->toBe((float) $opportunity->estimated_deal_amount);
});

it('lets users advance or cancel an opportunity from the detail page', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $opportunity = Opportunity::where('stage', 'Done Deal')->first();
    $opportunity->update(['stage' => 'Proposal', 'probability' => 60, 'lost_reason' => null]);

    $this->actingAs($sales)
        ->get(route('crm.show', ['opportunities', $opportunity->id]))
        ->assertOk()
        ->assertSee('推進至 洽談')
        ->assertSee('取消 OP');

    $this->actingAs($sales)
        ->post(route('opportunities.stage', $opportunity), ['action' => 'advance'])
        ->assertRedirect(route('crm.show', ['opportunities', $opportunity->id]));

    expect($opportunity->refresh()->stage)->toBe('Negotiation')
        ->and($opportunity->probability)->toBe(80)
        ->and(OpportunityActivity::where('opportunity_id', $opportunity->id)->latest('id')->value('body'))->toBe('Moved to Negotiation');

    $this->actingAs($sales)
        ->post(route('opportunities.stage', $opportunity), ['action' => 'cancel'])
        ->assertRedirect(route('crm.show', ['opportunities', $opportunity->id]));

    expect($opportunity->refresh()->stage)->toBe('Lost')
        ->and($opportunity->probability)->toBe(0)
        ->and($opportunity->lost_reason)->toBe('Cancelled from OP detail page.')
        ->and(OpportunityActivity::where('opportunity_id', $opportunity->id)->latest('id')->value('body'))->toBe('Cancelled OP');
});

it('lets sales create renewal deals directly from an account and keeps new deals at fixed plan price', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $admin = User::where('email', 'admin@example.com')->first();
    $account = Account::first();
    $planB = SalesPlan::where('plan_name', 'PLAN B')->first();

    $this->actingAs($sales)
        ->get(route('crm.create', ['module' => 'deals', 'account_id' => $account->id, 'deal_type' => 'am_managed_renewal']))
        ->assertOk()
        ->assertSee('續約 / 升級 Deal 指引')
        ->assertSee('Sales 新增會自動列為 AM Renewal');

    $this->actingAs($sales)->post(route('crm.store', 'deals'), [
        'account_id' => $account->id,
        'sales_user_id' => $admin->id,
        'account_manager_id' => $admin->id,
        'sales_plan_id' => $planB->id,
        'deal_type' => 'am_managed_renewal',
        'deal_amount' => 88000,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ])->assertRedirect();

    $renewal = Deal::where('deal_type', 'am_managed_renewal')->latest('id')->first();
    expect((float) $renewal->deal_amount)->toBe(88000.0)
        ->and($renewal->sales_user_id)->toBe($sales->id)
        ->and($renewal->account_manager_id)->toBe($account->account_manager_id);

    $this->actingAs($sales)->post(route('crm.store', 'deals'), [
        'account_id' => $account->id,
        'sales_user_id' => $admin->id,
        'account_manager_id' => $admin->id,
        'sales_plan_id' => $planB->id,
        'deal_type' => 'new_deal',
        'deal_amount' => 1,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ])->assertRedirect();

    $newDeal = Deal::where('deal_type', 'new_deal')->latest('id')->first();
    expect((float) $newDeal->deal_amount)->toBe((float) $planB->selling_price)
        ->and($newDeal->sales_user_id)->toBe($sales->id);
});

it('requires a successful done deal before adding a renewal deal', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $planB = SalesPlan::where('plan_name', 'PLAN B')->first();
    $account = Account::create([
        'company_name' => 'No Success Clinic',
        'company_registration_number' => 'NO-SUCCESS-001',
        'account_manager_id' => $sales->id,
        'created_by' => $sales->id,
        'status' => 'active',
    ]);

    $this->actingAs($sales)->post(route('crm.store', 'deals'), [
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planB->id,
        'deal_type' => 'am_managed_renewal',
        'deal_amount' => 88000,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ])->assertSessionHasErrors('account_id');

    expect(Deal::where('account_id', $account->id)->exists())->toBeFalse();
});

it('classifies staff and admin renewal deals as passive automatically', function () {
    $staff = User::where('email', 'staff@example.com')->first();
    $admin = User::where('email', 'admin@example.com')->first();
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    $planB = SalesPlan::where('plan_name', 'PLAN B')->first();

    $this->actingAs($staff)->post(route('crm.store', 'deals'), [
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planB->id,
        'deal_type' => 'am_managed_renewal',
        'deal_amount' => 88000,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('crm.store', 'deals'), [
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planB->id,
        'deal_type' => 'am_managed_renewal',
        'deal_amount' => 99000,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ])->assertRedirect();

    expect(Deal::where('deal_amount', 88000)->value('deal_type'))->toBe('passive_renewal_upgrade')
        ->and(Deal::where('deal_amount', 99000)->value('deal_type'))->toBe('passive_renewal_upgrade');
});

it('calculates new deal, renewal rules, cumulative tier, and excludes passive count-in', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));

    expect((float) $run->basic_commission)->toBe(4000.0)
        ->and((float) $run->renewal_upgrade_commission)->toBe(7200.0)
        ->and((float) $run->monthly_qualified_sales_amount)->toBe(133000.0)
        ->and((float) $run->monthly_tier_bonus)->toBe(1500.0);

    $planA = SalesPlan::where('plan_name', 'PLAN A')->first();
    Deal::create([
        'account_id' => Account::first()->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planA->id,
        'deal_type' => 'passive_renewal_upgrade',
        'deal_amount' => 32000,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ]);

    $rerun = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $rerun->monthly_qualified_sales_amount)->toBe(133000.0)
        ->and((float) $rerun->renewal_upgrade_commission)->toBe(7680.0)
        ->and(CommissionRun::count())->toBe(1);
});

it('uses plan monthly tier eligibility instead of hard-coded plan codes', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    Deal::query()->delete();

    $planA = SalesPlan::where('plan_name', 'PLAN A')->first();
    $planB = SalesPlan::where('plan_name', 'PLAN B')->first();

    foreach (range(1, 4) as $index) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $planA->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $planA->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
            'notes' => 'PLAN A count-only deal '.$index,
        ]);
    }

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->monthly_qualified_sales_amount)->toBe(128000.0)
        ->and((float) $run->monthly_tier_bonus)->toBe(0.0);

    Deal::query()->delete();
    foreach (range(1, 2) as $index) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $planB->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $planB->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
            'notes' => 'PLAN B trigger deal '.$index,
        ]);
    }

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->monthly_qualified_sales_amount)->toBe(122000.0)
        ->and((float) $run->monthly_tier_bonus)->toBe(1500.0);
});

it('lets custom plans define tier count-in, tier trigger, and hpa level', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    Deal::query()->delete();

    $custom = SalesPlan::create([
        'plan_code' => 'PLAN-9999',
        'plan_name' => 'PLAN X',
        'selling_price' => 200000,
        'report_commitment' => 300,
        'average_cost_per_report' => 200,
        'included_ipad_quantity' => 1,
        'included_sensor_set_quantity' => 2,
        'monthly_tier_count_percentage' => 50,
        'can_trigger_monthly_tier' => false,
        'hpa_eligible' => false,
        'hpa_level' => 'none',
        'is_active' => true,
        'display_order' => 99,
    ]);
    CommissionRule::create([
        'key' => 'new_deal_commission_PLAN-9999',
        'name' => 'PLAN X 新成交佣金',
        'type' => 'amount',
        'value' => 1000,
        'sales_plan_id' => $custom->id,
        'is_active' => true,
    ]);

    Deal::create([
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $custom->id,
        'deal_type' => 'new_deal',
        'deal_amount' => $custom->selling_price,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ]);

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->monthly_qualified_sales_amount)->toBe(100000.0)
        ->and((float) $run->monthly_tier_bonus)->toBe(0.0);

    $custom->update(['can_trigger_monthly_tier' => true]);
    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->monthly_tier_bonus)->toBe(1500.0);

    Deal::query()->delete();
    $custom->update([
        'monthly_tier_count_percentage' => 100,
        'hpa_eligible' => true,
        'hpa_level' => 'none',
    ]);
    $customRule = HighPlanAcceleratorRule::create([
        'code' => 'HPA-CUSTOM',
        'name' => '2 x PLAN X',
        'min_hpa_eligible_deals' => 2,
        'bonus' => 5000,
        'priority' => 10,
        'is_active' => true,
    ]);
    $customRule->salesPlanRequirements()->sync([$custom->id => ['required_quantity' => 2]]);

    foreach (range(1, 2) as $index) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $custom->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $custom->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
            'notes' => 'Custom HPA deal '.$index,
        ]);
    }

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->high_plan_accelerator_bonus)->toBe(5000.0);
});

it('calculates high plan accelerator by per-plan hpa opt-in quantities and pays only the highest matching rule', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    Deal::query()->delete();

    $planA = SalesPlan::where('plan_name', 'PLAN A')->first();
    $planC = SalesPlan::where('plan_name', 'PLAN C')->first();
    $planD = SalesPlan::where('plan_name', 'PLAN D')->first();

    foreach ([$planA, $planC, $planD] as $plan) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $plan->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $plan->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
        ]);
    }

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->high_plan_accelerator_bonus)->toBe(8000.0);

    Deal::query()->delete();
    foreach ([$planC, $planC] as $plan) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $plan->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $plan->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
        ]);
    }

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->high_plan_accelerator_bonus)->toBe(5000.0);

    Deal::query()->delete();
    foreach ([$planC, $planD, $planD] as $plan) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $plan->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $plan->selling_price,
            'payment_status' => 'Paid',
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
        ]);
    }

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->high_plan_accelerator_bonus)->toBe(12000.0);

    Deal::query()->delete();
    Deal::create([
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planC->id,
        'deal_type' => 'new_deal',
        'deal_amount' => $planC->selling_price,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ]);

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->high_plan_accelerator_bonus)->toBe(0.0);
});

it('excludes pending cancelled refunded and renewal deals from hpa eligible count', function () {
    $sales = User::where('email', 'sales@example.com')->first();
    $account = Account::first();
    $planC = SalesPlan::where('plan_name', 'PLAN C')->first();
    Deal::query()->delete();

    foreach (['Pending', 'Cancelled', 'Refunded'] as $status) {
        Deal::create([
            'account_id' => $account->id,
            'sales_user_id' => $sales->id,
            'account_manager_id' => $sales->id,
            'sales_plan_id' => $planC->id,
            'deal_type' => 'new_deal',
            'deal_amount' => $planC->selling_price,
            'payment_status' => $status,
            'payment_date' => now()->toDateString(),
            'commission_status' => 'Pending',
        ]);
    }

    Deal::create([
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planC->id,
        'deal_type' => 'am_managed_upgrade',
        'deal_amount' => $planC->selling_price,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ]);

    Deal::create([
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $planC->id,
        'deal_type' => 'new_deal',
        'deal_amount' => $planC->selling_price,
        'payment_status' => 'Paid',
        'payment_date' => now()->toDateString(),
        'commission_status' => 'Pending',
    ]);

    $run = app(CommissionCalculator::class)->calculate($sales, now()->format('Y-m'));
    expect((float) $run->high_plan_accelerator_bonus)->toBe(0.0);
});
