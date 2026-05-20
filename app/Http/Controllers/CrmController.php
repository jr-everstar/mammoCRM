<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Models\OpportunityStageRule;
use App\Models\SalesPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function index(Request $request, string $module): View
    {
        [$model, $title] = $this->module($module);
        $query = $this->visible($model::query(), $module, $request->user())->with($this->with($module))->latest();

        if ($search = $request->string('search')->toString()) {
            $query->where(function (Builder $builder) use ($module, $search) {
                foreach ($this->searchColumns($module) as $column) {
                    $builder->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if ($module === 'leads' && $status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($module === 'opportunities') {
            if ($stage = $request->string('stage')->toString()) {
                $query->where('stage', $stage);
            }

            if ($request->string('close')->toString() === 'this_month') {
                $query->whereBetween('expected_close_date', [now()->startOfMonth(), now()->endOfMonth()]);
            }

            if ($request->string('close')->toString() === 'overdue') {
                $query->whereNotIn('stage', ['Done Deal', 'Lost'])
                    ->whereDate('expected_close_date', '<', now()->toDateString());
            }
        }

        return view('crm.index', [
            'module' => $module,
            'title' => $title,
            'records' => $query->paginate(15)->withQueryString(),
            'columns' => $this->columns($module),
            'leadStatuses' => $this->leadStatuses(),
            'stages' => Opportunity::STAGES,
            'indexStats' => $this->indexStats($module, clone $query),
        ]);
    }

    public function create(Request $request, string $module): View
    {
        return view('crm.form', $this->formData($module));
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request, $module);

        if ($module === 'accounts') {
            $data['created_by'] = $user->id;
            $data['account_manager_id'] = $user->canManageCrm() ? ($data['account_manager_id'] ?? $user->id) : $user->id;
            DB::transaction(function () use ($data) {
                $account = Account::create($data);

                $this->syncPrimaryContactFromAccount($account);
            });
        } elseif ($module === 'contacts') {
            $account = Account::findOrFail($data['account_id']);
            abort_unless($user->canManageCrm() || $account->account_manager_id === $user->id || $account->created_by === $user->id, 403);
            $data['is_primary'] = (bool) ($data['is_primary'] ?? false) || ! $account->contacts()->exists();

            DB::transaction(function () use ($account, $data) {
                if ($data['is_primary']) {
                    $this->clearOtherPrimaryContacts($account);
                }

                Contact::create($data);
            });
        } elseif ($module === 'leads') {
            $data['created_by'] = $user->id;
            $data['assigned_sales_id'] = $user->canManageCrm() ? ($data['assigned_sales_id'] ?? $user->id) : $user->id;
            Lead::create($data);
        } elseif ($module === 'opportunities') {
            $account = Account::findOrFail($data['account_id']);
            abort_unless($user->canManageCrm() || $account->account_manager_id === $user->id || $account->created_by === $user->id, 403);
            $data['assigned_sales_id'] = $user->canManageCrm() ? ($data['assigned_sales_id'] ?? $account->account_manager_id) : $user->id;
            $data['estimated_deal_amount'] = SalesPlan::findOrFail($data['sales_plan_id'])->selling_price;
            $data['probability'] = $this->stageProbability($data['stage']);
            Opportunity::create($data);
        } else {
            $account = Account::findOrFail($data['account_id']);
            abort_unless($user->canManageCrm() || ($user->hasRole('sales') && ($account->account_manager_id === $user->id || $account->created_by === $user->id)), 403);

            if (! $user->canManageCrm()) {
                $data['sales_user_id'] = $user->id;
                $data['account_manager_id'] = $account->account_manager_id;
            }

            $data['deal_type'] = $this->resolvedDealType($data['deal_type'] ?? 'new_deal', $user);
            $this->ensureRenewalHasSuccessfulDeal($account, $data['deal_type']);

            if (($data['deal_type'] ?? null) === 'new_deal' && ! empty($data['sales_plan_id'])) {
                $data['deal_amount'] = SalesPlan::findOrFail($data['sales_plan_id'])->selling_price;
            }
            Deal::create($data);
        }

        return to_route('crm.index', $module)->with('status', 'Saved.');
    }

    public function show(Request $request, string $module, int $id): View
    {
        [$model, $title] = $this->module($module);
        $with = $this->with($module);

        if (in_array($module, ['leads', 'opportunities'], true)) {
            $with[] = 'comments.author';
        }

        $record = $this->visible($model::query(), $module, $request->user())->with($with)->findOrFail($id);

        return view('crm.show', [
            'module' => $module,
            'title' => $title,
            'record' => $record,
            'columns' => $this->columns($module),
            'plans' => SalesPlan::where('is_active', true)->orderBy('display_order')->get(),
            'stages' => Opportunity::STAGES,
            'accountRelated' => $module === 'accounts' ? $this->accountRelated($record) : [],
        ]);
    }

    public function edit(Request $request, string $module, int $id): View
    {
        [$model] = $this->module($module);
        $record = $this->visible($model::query(), $module, $request->user())->findOrFail($id);

        return view('crm.form', $this->formData($module, $record));
    }

    public function update(Request $request, string $module, int $id): RedirectResponse
    {
        [$model] = $this->module($module);
        $record = $this->visible($model::query(), $module, $request->user())->findOrFail($id);
        $data = $this->validated($request, $module, $id);

        if ($module === 'accounts' && ! $request->user()->canManageCrm()) {
            unset($data['account_manager_id']);
        }

        if ($module === 'contacts') {
            $account = Account::findOrFail($data['account_id']);
            abort_unless($request->user()->canManageCrm() || $account->account_manager_id === $request->user()->id || $account->created_by === $request->user()->id, 403);

            $data['is_primary'] = (bool) ($data['is_primary'] ?? false)
                || ! $account->contacts()->whereKeyNot($record->id)->exists();
        }

        if ($module === 'opportunities') {
            $data['estimated_deal_amount'] = SalesPlan::findOrFail($data['sales_plan_id'])->selling_price;
            $data['probability'] = $this->stageProbability($data['stage']);
        }

        if ($module === 'deals') {
            $account = Account::findOrFail($data['account_id']);
            abort_unless($request->user()->canManageCrm() || ($request->user()->hasRole('sales') && ($account->account_manager_id === $request->user()->id || $account->created_by === $request->user()->id)), 403);

            if (! $request->user()->canManageCrm()) {
                $data['sales_user_id'] = $request->user()->id;
                $data['account_manager_id'] = $account->account_manager_id;
            }

            $data['deal_type'] = $this->resolvedDealType($data['deal_type'] ?? $record->deal_type, $request->user());
            $this->ensureRenewalHasSuccessfulDeal($account, $data['deal_type'], $record->id);

            if (($data['deal_type'] ?? null) === 'new_deal' && ! empty($data['sales_plan_id'])) {
                $data['deal_amount'] = SalesPlan::findOrFail($data['sales_plan_id'])->selling_price;
            }
        }

        DB::transaction(function () use ($module, $record, $data) {
            if ($module === 'contacts' && $data['is_primary']) {
                $this->clearOtherPrimaryContacts(Account::findOrFail($data['account_id']), $record);
            }

            $record->update($data);

            if ($module === 'accounts') {
                $this->syncPrimaryContactFromAccount($record);
            }
        });

        return to_route('crm.show', [$module, $record])->with('status', 'Updated.');
    }

    public function updateRemarks(Request $request, string $module, int $id): RedirectResponse
    {
        abort_unless(in_array($module, ['leads', 'opportunities'], true), 404);

        [$model] = $this->module($module);
        $record = $this->visible($model::query(), $module, $request->user())->findOrFail($id);
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $record->update(['notes' => $data['notes'] ?? null]);

        return back()->with('status', '備註已儲存。');
    }

    public function storeComment(Request $request, string $module, int $id): RedirectResponse
    {
        abort_unless(in_array($module, ['leads', 'opportunities'], true), 404);

        [$model] = $this->module($module);
        $record = $this->visible($model::query(), $module, $request->user())->findOrFail($id);
        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $record->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Comment saved.');
    }

    public function destroy(Request $request, string $module, int $id): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || ($request->user()->canManageCrm() && in_array($module, ['accounts', 'contacts', 'leads', 'opportunities'], true)), 403);
        [$model] = $this->module($module);
        $model::findOrFail($id)->delete();

        return to_route('crm.index', $module)->with('status', 'Deleted.');
    }

    public function convertLead(Request $request, Lead $lead): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageCrm() || $lead->assigned_sales_id === $user->id || $lead->created_by === $user->id, 403);

        $data = $request->validate([
            'company_registration_number' => ['required', 'string', 'max:255'],
            'opportunity_name' => ['required', 'string', 'max:255'],
            'sales_plan_id' => ['required', 'exists:sales_plans,id'],
            'expected_close_date' => ['nullable', 'date'],
        ]);
        $plan = SalesPlan::findOrFail($data['sales_plan_id']);

        DB::transaction(function () use ($lead, $data, $user, $plan) {
            $account = Account::firstOrCreate(
                ['company_registration_number' => $data['company_registration_number']],
                [
                    'company_name' => $lead->company_name ?: $lead->lead_name,
                    'business_type' => $lead->business_type,
                    'contact_person_name' => $lead->contact_person,
                    'contact_phone' => $lead->contact_phone,
                    'contact_email' => $lead->contact_email,
                    'account_manager_id' => $lead->assigned_sales_id,
                    'created_by' => $user->id,
                    'status' => 'prospect',
                    'notes' => $lead->notes,
                ]
            );

            $this->syncPrimaryContactFromLead($account, $lead);

            $opportunity = Opportunity::create([
                'account_id' => $account->id,
                'opportunity_name' => $data['opportunity_name'],
                'sales_plan_id' => $plan->id,
                'estimated_deal_amount' => $plan->selling_price,
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'assigned_sales_id' => $lead->assigned_sales_id,
                'stage' => 'Lead-in',
                'probability' => $this->stageProbability('Lead-in'),
                'notes' => 'Converted from lead: '.$lead->lead_name,
            ]);

            $lead->update([
                'status' => 'Converted',
                'company_registration_number' => $data['company_registration_number'],
                'converted_at' => now(),
                'converted_account_id' => $account->id,
                'converted_opportunity_id' => $opportunity->id,
            ]);
        });

        return to_route('crm.show', ['leads', $lead])->with('status', 'Lead converted.');
    }

    public function updateOpportunityStage(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($request->user()->canManageCrm() || $opportunity->assigned_sales_id === $request->user()->id, 403);

        $data = $request->validate([
            'action' => ['required', 'in:advance,cancel'],
        ]);

        if (in_array($opportunity->stage, ['Done Deal', 'Lost'], true)) {
            return back()->with('status', 'This OP is already closed.');
        }

        $stage = $data['action'] === 'cancel'
            ? 'Lost'
            : $this->nextOpportunityStage($opportunity->stage);

        if (! $stage) {
            return back()->with('status', 'No next stage is available for this OP.');
        }

        DB::transaction(function () use ($request, $opportunity, $stage, $data) {
            $opportunity->update([
                'stage' => $stage,
                'lost_reason' => $stage === 'Lost' ? 'Cancelled from OP detail page.' : null,
                'probability' => $this->stageProbability($stage),
            ]);

            OpportunityActivity::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => $request->user()->id,
                'type' => 'stage_change',
                'body' => $data['action'] === 'cancel'
                    ? 'Cancelled OP'
                    : 'Moved to '.$stage,
            ]);

            if ($stage === 'Done Deal') {
                Deal::firstOrCreate(
                    ['opportunity_id' => $opportunity->id],
                    [
                        'account_id' => $opportunity->account_id,
                        'sales_user_id' => $opportunity->assigned_sales_id,
                        'account_manager_id' => $opportunity->account->account_manager_id,
                        'sales_plan_id' => $opportunity->sales_plan_id,
                        'deal_type' => 'new_deal',
                        'deal_amount' => $opportunity->estimated_deal_amount,
                        'payment_status' => 'Pending',
                        'payment_date' => null,
                        'contract_date' => null,
                        'commission_status' => 'Pending',
                    ]
                );
            }
        });

        return to_route('crm.show', ['opportunities', $opportunity])->with('status', 'OP stage updated.');
    }

    private function syncPrimaryContactFromLead(Account $account, Lead $lead): void
    {
        if (blank($lead->contact_person) && blank($lead->contact_phone) && blank($lead->contact_email)) {
            return;
        }

        $identity = filled($lead->contact_email)
            ? ['account_id' => $account->id, 'email' => $lead->contact_email]
            : [
                'account_id' => $account->id,
                'name' => $lead->contact_person ?: $lead->lead_name,
                'phone' => $lead->contact_phone,
            ];

        Contact::updateOrCreate($identity, [
            'name' => $lead->contact_person ?: $lead->lead_name,
            'phone' => $lead->contact_phone,
            'email' => $lead->contact_email,
            'whatsapp' => $lead->contact_phone,
            'is_primary' => true,
            'status' => 'active',
            'notes' => $lead->notes,
        ]);
    }

    private function syncPrimaryContactFromAccount(Account $account): void
    {
        if (
            blank($account->contact_person_name)
            && blank($account->contact_person_title)
            && blank($account->contact_phone)
            && blank($account->contact_email)
        ) {
            return;
        }

        $contact = null;

        if (filled($account->contact_email)) {
            $contact = $account->contacts()->where('email', $account->contact_email)->first();
        }

        if (! $contact && filled($account->contact_person_name)) {
            $contact = $account->contacts()
                ->where('name', $account->contact_person_name)
                ->when(
                    filled($account->contact_phone),
                    fn ($query) => $query->where('phone', $account->contact_phone)
                )
                ->first();
        }

        if (! $contact) {
            $contact = $account->contacts()->where('is_primary', true)->oldest('id')->first();
        }

        $data = [
            'name' => $account->contact_person_name ?: $account->company_name,
            'title' => $account->contact_person_title,
            'phone' => $account->contact_phone,
            'email' => $account->contact_email,
            'whatsapp' => $account->contact_phone,
            'is_primary' => true,
            'status' => 'active',
            'notes' => $account->notes,
        ];

        if ($contact) {
            $this->clearOtherPrimaryContacts($account, $contact);
            $contact->update($data);

            return;
        }

        $this->clearOtherPrimaryContacts($account);
        $account->contacts()->create($data);
    }

    private function clearOtherPrimaryContacts(Account $account, ?Contact $except = null): void
    {
        $account->contacts()
            ->where('is_primary', true)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->update(['is_primary' => false]);
    }

    private function module(string $module): array
    {
        return match ($module) {
            'accounts' => [Account::class, '客戶 / 商戶帳戶'],
            'contacts' => [Contact::class, '聯絡人 Contacts'],
            'leads' => [Lead::class, '商機 (Leads)'],
            'opportunities' => [Opportunity::class, '機會 (OP)'],
            'deals' => [Deal::class, '成交記錄'],
            default => abort(404),
        };
    }

    private function visible(Builder $query, string $module, User $user): Builder
    {
        if ($user->canManageCrm() && in_array($module, ['accounts', 'contacts', 'leads', 'opportunities', 'deals'], true)) {
            return $query;
        }

        return match ($module) {
            'accounts' => $query->where(fn ($q) => $q->where('account_manager_id', $user->id)->orWhere('created_by', $user->id)),
            'contacts' => $query->whereHas('account', fn ($q) => $q->where('account_manager_id', $user->id)->orWhere('created_by', $user->id)),
            'leads' => $query->where(fn ($q) => $q->where('assigned_sales_id', $user->id)->orWhere('created_by', $user->id)),
            'opportunities' => $query->where('assigned_sales_id', $user->id),
            'deals' => $query->where(fn ($q) => $q->where('sales_user_id', $user->id)->orWhere('account_manager_id', $user->id)),
            default => $query,
        };
    }

    private function validated(Request $request, string $module, ?int $id = null): array
    {
        return match ($module) {
            'accounts' => $request->validate([
                'company_name' => ['required', 'string', 'max:255'],
                'company_registration_number' => ['required', 'string', 'max:255', 'unique:accounts,company_registration_number,'.($id ?? 'NULL')],
                'business_type' => ['nullable', 'string', 'max:255'],
                'contact_person_name' => ['nullable', 'string', 'max:255'],
                'contact_person_title' => ['nullable', 'string', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:255'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string'],
                'account_manager_id' => ['nullable', 'exists:users,id'],
                'status' => ['required', 'in:prospect,active,inactive,lost'],
                'notes' => ['nullable', 'string'],
            ]),
            'contacts' => $request->validate([
                'account_id' => ['required', 'exists:accounts,id'],
                'name' => ['required', 'string', 'max:255'],
                'title' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'whatsapp' => ['nullable', 'string', 'max:255'],
                'is_primary' => ['nullable', 'boolean'],
                'status' => ['required', 'in:active,inactive'],
                'notes' => ['nullable', 'string'],
            ]),
            'leads' => $request->validate([
                'lead_name' => ['required', 'string', 'max:255'],
                'company_name' => ['nullable', 'string', 'max:255'],
                'company_registration_number' => ['nullable', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:255'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'source' => ['nullable', 'string', 'max:255'],
                'business_type' => ['nullable', 'string', 'max:255'],
                'assigned_sales_id' => ['nullable', 'exists:users,id'],
                'status' => ['required', 'in:New,Contacted,Meeting Scheduled,Demo Completed,Trial Arranged,Trial Completed,Converted,Lost'],
                'notes' => ['nullable', 'string'],
            ]),
            'opportunities' => $request->validate([
                'account_id' => ['required', 'exists:accounts,id'],
                'opportunity_name' => ['required', 'string', 'max:255'],
                'sales_plan_id' => ['required', 'exists:sales_plans,id'],
                'expected_close_date' => ['nullable', 'date'],
                'assigned_sales_id' => ['nullable', 'exists:users,id'],
                'stage' => ['required', 'in:'.implode(',', Opportunity::STAGES)],
                'notes' => ['nullable', 'string'],
                'lost_reason' => ['nullable', 'required_if:stage,Lost', 'string'],
            ]),
            'deals' => $request->validate([
                'account_id' => ['required', 'exists:accounts,id'],
                'opportunity_id' => ['nullable', 'exists:opportunities,id'],
                'sales_user_id' => ['required', 'exists:users,id'],
                'account_manager_id' => ['required', 'exists:users,id'],
                'sales_plan_id' => ['nullable', 'exists:sales_plans,id'],
                'deal_type' => ['nullable', 'in:new_deal,passive_renewal_upgrade,am_managed_renewal,am_managed_upgrade'],
                'deal_amount' => ['required', 'numeric', 'min:0'],
                'payment_status' => ['required', 'in:Pending,Paid,Cancelled,Refunded'],
                'payment_date' => ['nullable', 'date'],
                'contract_date' => ['nullable', 'date'],
                'commission_status' => ['required', 'in:Pending,Calculated,Approved,Paid,Cancelled'],
                'notes' => ['nullable', 'string'],
            ]),
        };
    }

    private function formData(string $module, mixed $record = null): array
    {
        $user = auth()->user();

        return [
            'module' => $module,
            'title' => $this->module($module)[1],
            'record' => $record,
            'isEdit' => filled($record),
            'accounts' => $user ? $this->visible(Account::query(), 'accounts', $user)->orderBy('company_name')->get() : collect(),
            'plans' => SalesPlan::where('is_active', true)->orderBy('display_order')->get(),
            'salesUsers' => User::role(['admin', 'sales'])->where('status', 'active')->orderBy('name')->get(),
            'stages' => Opportunity::STAGES,
        ];
    }

    private function resolvedDealType(string $requestedDealType, User $user): string
    {
        if (! $this->isRenewalDealType($requestedDealType)) {
            return 'new_deal';
        }

        return $user->hasRole('sales') && ! $user->canManageCrm()
            ? 'am_managed_renewal'
            : 'passive_renewal_upgrade';
    }

    private function ensureRenewalHasSuccessfulDeal(Account $account, string $dealType, ?int $exceptDealId = null): void
    {
        if (! $this->isRenewalDealType($dealType)) {
            return;
        }

        $hasSuccessfulDeal = $account->deals()
            ->where('deal_type', 'new_deal')
            ->whereNotIn('payment_status', ['Cancelled', 'Refunded'])
            ->when($exceptDealId, fn ($query) => $query->whereKeyNot($exceptDealId))
            ->exists();

        if (! $hasSuccessfulDeal) {
            throw ValidationException::withMessages([
                'account_id' => 'This account needs a successful Done Deal before adding a renewal deal.',
            ]);
        }
    }

    private function isRenewalDealType(string $dealType): bool
    {
        return in_array($dealType, ['passive_renewal_upgrade', 'am_managed_renewal', 'am_managed_upgrade'], true);
    }

    private function nextOpportunityStage(string $stage): ?string
    {
        $stages = array_values(array_filter(
            Opportunity::STAGES,
            fn (string $pipelineStage) => $pipelineStage !== 'Lost'
        ));
        $index = array_search($stage, $stages, true);

        return $index === false ? null : ($stages[$index + 1] ?? null);
    }

    private function with(string $module): array
    {
        return match ($module) {
            'accounts' => ['manager'],
            'contacts' => ['account'],
            'leads' => ['assignedSales'],
            'opportunities' => ['account', 'salesPlan', 'assignedSales', 'trialAgreements.assets'],
            'deals' => ['account', 'salesPlan', 'salesUser'],
            default => [],
        };
    }

    private function columns(string $module): array
    {
        return match ($module) {
            'accounts' => ['company_name', 'company_registration_number', 'business_type', 'status'],
            'contacts' => ['name', 'title', 'email', 'phone', 'status'],
            'leads' => ['lead_name', 'company_name', 'source', 'status'],
            'opportunities' => ['opportunity_name', 'stage', 'estimated_deal_amount', 'expected_close_date'],
            'deals' => ['deal_type', 'deal_amount', 'payment_status', 'payment_date', 'commission_status'],
        };
    }

    private function leadStatuses(): array
    {
        return ['New', 'Contacted', 'Meeting Scheduled', 'Demo Completed', 'Trial Arranged', 'Trial Completed', 'Converted', 'Lost'];
    }

    private function indexStats(string $module, Builder $query): array
    {
        return match ($module) {
            'leads' => [
                'total' => (clone $query)->count(),
                'new' => (clone $query)->where('status', 'New')->count(),
                'active' => (clone $query)->whereNotIn('status', ['Converted', 'Lost'])->count(),
                'converted' => (clone $query)->where('status', 'Converted')->count(),
            ],
            'opportunities' => [
                'total' => (clone $query)->count(),
                'pipeline' => (float) (clone $query)->whereNotIn('stage', ['Done Deal', 'Lost'])->sum('estimated_deal_amount'),
                'closing_month' => (float) (clone $query)->whereBetween('expected_close_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('estimated_deal_amount'),
                'won' => (clone $query)->where('stage', 'Done Deal')->count(),
            ],
            default => [],
        };
    }

    private function searchColumns(string $module): array
    {
        return match ($module) {
            'accounts' => ['company_name', 'company_registration_number', 'contact_person_name'],
            'contacts' => ['name', 'title', 'email', 'phone'],
            'leads' => ['lead_name', 'company_name', 'contact_person'],
            'opportunities' => ['opportunity_name', 'stage'],
            'deals' => ['deal_type', 'payment_status'],
        };
    }

    private function accountRelated(Account $account): array
    {
        return [
            'contacts' => $account->contacts()->latest()->get(),
            'leads' => $account->leads()->latest()->get(),
            'opportunities' => $account->opportunities()->with(['salesPlan', 'assignedSales'])->latest()->get(),
            'deals' => $account->deals()->with(['salesPlan', 'salesUser'])->latest()->get(),
            'quotations' => $account->quotations()->latest()->get(),
            'contracts' => $account->contracts()->latest()->get(),
            'trialAgreements' => $account->trialAgreementDocuments()->latest()->get(),
            'invoices' => $account->invoices()->latest()->get(),
        ];
    }

    private function stageProbability(string $stage): int
    {
        return (int) OpportunityStageRule::where('stage', $stage)->value('probability');
    }
}
