<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Models\OpportunityStageRule;
use App\Models\SalesPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KanbanController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = Opportunity::with(['account', 'salesPlan', 'assignedSales'])->latest('updated_at');

        if (! $request->user()->canManageCrm()) {
            $query->where('assigned_sales_id', $request->user()->id);
        }

        return view('crm.kanban', [
            'stages' => Opportunity::STAGES,
            'opportunities' => $query->get()->groupBy('stage'),
            'plans' => SalesPlan::where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function move(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($request->user()->canManageCrm() || $opportunity->assigned_sales_id === $request->user()->id, 403);

        $data = $request->validate([
            'stage' => ['required', 'in:'.implode(',', Opportunity::STAGES)],
            'lost_reason' => ['nullable', 'required_if:stage,Lost', 'string'],
            'deal_amount' => ['nullable', 'numeric', 'min:0'],
            'sales_plan_id' => ['nullable', 'exists:sales_plans,id'],
            'payment_status' => ['nullable', 'in:Pending,Paid,Cancelled,Refunded'],
            'payment_date' => ['nullable', 'date'],
            'contract_date' => ['nullable', 'date'],
            'deal_type' => ['nullable', 'in:new_deal,passive_renewal_upgrade,am_managed_renewal,am_managed_upgrade'],
        ]);

        DB::transaction(function () use ($opportunity, $data, $request) {
            $opportunity->update([
                'stage' => $data['stage'],
                'lost_reason' => $data['stage'] === 'Lost' ? $data['lost_reason'] : null,
                'sales_plan_id' => $data['sales_plan_id'] ?? $opportunity->sales_plan_id,
                'probability' => (int) OpportunityStageRule::where('stage', $data['stage'])->value('probability'),
            ]);

            OpportunityActivity::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => $request->user()->id,
                'type' => 'stage_change',
                'body' => 'Moved to '.$data['stage'],
            ]);

            if ($data['stage'] === 'Done Deal') {
                Deal::firstOrCreate(
                    ['opportunity_id' => $opportunity->id],
                    [
                        'account_id' => $opportunity->account_id,
                        'sales_user_id' => $opportunity->assigned_sales_id,
                        'account_manager_id' => $opportunity->account->account_manager_id,
                        'sales_plan_id' => $data['sales_plan_id'] ?? $opportunity->sales_plan_id,
                        'deal_type' => $data['deal_type'] ?? 'new_deal',
                        'deal_amount' => $data['deal_amount'] ?? $opportunity->estimated_deal_amount,
                        'payment_status' => $data['payment_status'] ?? 'Pending',
                        'payment_date' => $data['payment_date'] ?? null,
                        'contract_date' => $data['contract_date'] ?? null,
                        'commission_status' => 'Pending',
                    ]
                );
            }
        });

        return response()->json(['ok' => true]);
    }
}
