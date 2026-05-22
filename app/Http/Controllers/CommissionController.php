<?php

namespace App\Http\Controllers;

use App\Models\CommissionRun;
use App\Models\SalesPlan;
use App\Models\User;
use App\Services\Commission\CommissionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = CommissionRun::with('salesUser')->latest('month');

        if (! $request->user()->isAdmin()) {
            $query->where('sales_user_id', $request->user()->id);
        }

        return view('commissions.index', [
            'runs' => $query->paginate(15),
            'salesUsers' => User::role('sales')->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function simulator(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'staff']), 403);

        return view('commissions.simulator', [
            'salesPlans' => $this->salesPlans(),
            'simulation' => null,
        ]);
    }

    public function simulate(Request $request, CommissionCalculator $calculator): View
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'staff']), 403);

        $data = $request->validate([
            'plan_counts' => ['nullable', 'array'],
            'plan_counts.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'operation_cost_buffer_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'admin_monthly_tier_override' => ['nullable', 'boolean'],
        ]);

        $simulation = $calculator->simulate(
            $data['plan_counts'] ?? [],
            (bool) ($data['admin_monthly_tier_override'] ?? false),
            (float) ($data['operation_cost_buffer_percentage'] ?? 0)
        );

        return view('commissions.simulator', [
            'salesPlans' => $this->salesPlans(),
            'simulation' => $simulation,
        ]);
    }

    public function run(Request $request, CommissionCalculator $calculator): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'month' => ['required', 'date'],
            'sales_user_id' => ['required', 'exists:users,id'],
            'admin_monthly_tier_override' => ['nullable', 'boolean'],
        ]);

        $calculator->calculate(User::findOrFail($data['sales_user_id']), $data['month'], (bool) ($data['admin_monthly_tier_override'] ?? false));

        return to_route('commissions.index')->with('status', 'Commission calculated.');
    }

    public function show(Request $request, CommissionRun $commissionRun): View
    {
        abort_unless($request->user()->isAdmin() || $commissionRun->sales_user_id === $request->user()->id, 403);

        return view('commissions.show', ['run' => $commissionRun->load('salesUser', 'items.deal.salesPlan')]);
    }

    public function approve(Request $request, CommissionRun $commissionRun): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $commissionRun->update([
            'status' => 'Approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $commissionRun->items()->with('deal')->get()->pluck('deal')->filter()->each->update(['commission_status' => 'Approved']);

        activity()->performedOn($commissionRun)->causedBy($request->user())->log('Commission approved');

        return back()->with('status', 'Commission approved.');
    }

    public function override(Request $request, CommissionRun $commissionRun): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'override_total_commission' => ['required', 'numeric', 'min:0'],
            'override_reason' => ['required', 'string', 'min:5'],
        ]);

        $commissionRun->update($data + ['status' => 'Calculated']);
        activity()->performedOn($commissionRun)->causedBy($request->user())->withProperties($data)->log('Commission override');

        return back()->with('status', 'Commission override saved.');
    }

    private function salesPlans()
    {
        return SalesPlan::where('is_active', true)->orderBy('display_order')->orderBy('plan_name')->get();
    }
}
