<?php

use App\Models\CommissionRun;
use App\Models\Deal;
use App\Models\SalesPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('lets admin and staff open the commission simulator', function () {
    $admin = User::where('email', 'admin@example.com')->first();
    $staff = User::where('email', 'staff@example.com')->first();

    $this->actingAs($admin)
        ->get(route('commissions.simulator'))
        ->assertOk()
        ->assertSee('佣金模擬器');

    $this->actingAs($staff)
        ->get(route('commissions.simulator'))
        ->assertOk()
        ->assertSee('佣金模擬器');
});

it('blocks sales users from the admin and staff simulator', function () {
    $sales = User::where('email', 'sales@example.com')->first();

    $this->actingAs($sales)
        ->get(route('commissions.simulator'))
        ->assertForbidden();
});

it('simulates manual signed plan counts without creating runs or updating deal statuses', function () {
    $staff = User::where('email', 'staff@example.com')->first();
    $planC = SalesPlan::where('plan_name', 'PLAN C')->first();
    $originalStatuses = Deal::pluck('commission_status', 'id')->all();

    $this->actingAs($staff)
        ->post(route('commissions.simulate'), [
            'plan_counts' => [
                $planC->id => 2,
            ],
            'operation_cost_buffer_percentage' => 10,
        ])
        ->assertOk()
        ->assertSee('HK$32,000.00')
        ->assertSee('HK$28,800.00')
        ->assertSee('HK$83,200.00')
        ->assertSee('PLAN C')
        ->assertSee('簽約方案')
        ->assertSee('公司淨利潤')
        ->assertSee('營運成本緩衝');

    expect(CommissionRun::count())->toBe(0)
        ->and(Deal::pluck('commission_status', 'id')->all())->toBe($originalStatuses);
});
