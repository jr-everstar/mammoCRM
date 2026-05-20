<?php

use App\Models\Account;
use App\Models\Deal;
use App\Models\Opportunity;
use App\Models\SalesPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('blocks non admin users from management reports', function () {
    $sales = User::where('email', 'sales@example.com')->firstOrFail();

    $this->actingAs($sales)
        ->get(route('reports.index'))
        ->assertForbidden();
});

it('shows management insights and resilient chart placeholders', function () {
    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $sales = User::where('email', 'sales@example.com')->firstOrFail();
    $account = Account::firstOrFail();
    $plan = SalesPlan::where('plan_name', 'PLAN A')->firstOrFail();

    Deal::create([
        'account_id' => $account->id,
        'sales_user_id' => $sales->id,
        'account_manager_id' => $sales->id,
        'sales_plan_id' => $plan->id,
        'deal_type' => 'new_deal',
        'deal_amount' => 32000,
        'payment_status' => 'Pending',
        'contract_date' => today()->toDateString(),
        'commission_status' => 'Pending',
    ]);

    Opportunity::create([
        'account_id' => $account->id,
        'opportunity_name' => 'Management forecast opportunity',
        'sales_plan_id' => $plan->id,
        'estimated_deal_amount' => 100000,
        'probability' => 60,
        'expected_close_date' => today()->addDays(10)->toDateString(),
        'assigned_sales_id' => $sales->id,
        'stage' => 'Proposal',
    ]);

    Opportunity::create([
        'account_id' => $account->id,
        'opportunity_name' => 'Overdue management opportunity',
        'sales_plan_id' => $plan->id,
        'estimated_deal_amount' => 50000,
        'probability' => 80,
        'expected_close_date' => today()->subDay()->toDateString(),
        'assigned_sales_id' => $sales->id,
        'stage' => 'Negotiation',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('管理層焦點')
        ->assertSee('Forecast 可見度')
        ->assertSee('Open Pipeline 預計金額')
        ->assertSee('data-report-chart', false)
        ->assertDontSee('new window.Chart', false);

    $metrics = $response->viewData('metrics');

    expect($metrics['unpaid_sales'])->toBe(32000.0)
        ->and($metrics['open_pipeline'])->toBe(150000.0)
        ->and($metrics['weighted_pipeline'])->toBe(100000.0)
        ->and($metrics['overdue_pipeline_count'])->toBe(1)
        ->and($metrics['overdue_pipeline_value'])->toBe(50000.0)
        ->and($metrics['closing_soon_count'])->toBe(1)
        ->and($metrics['closing_soon_value'])->toBe(100000.0);
});
