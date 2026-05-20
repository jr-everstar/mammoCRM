<?php

use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\MicrosoftEntraController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrialAgreementController;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/microsoft/redirect', [MicrosoftEntraController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('auth/microsoft/callback', [MicrosoftEntraController::class, 'callback'])->name('auth.microsoft.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = request()->user();
        $crmScopeUser = $user->canManageCrm() ? null : $user->id;
        $financialScopeUser = $user->isAdmin() ? null : $user->id;

        return view('dashboard', [
            'leadCount' => Lead::when($crmScopeUser, fn ($q) => $q->where('assigned_sales_id', $crmScopeUser))->count(),
            'opportunityCount' => Opportunity::when($crmScopeUser, fn ($q) => $q->where('assigned_sales_id', $crmScopeUser))->count(),
            'dealCount' => Deal::when($financialScopeUser, fn ($q) => $q->where('sales_user_id', $financialScopeUser))->count(),
            'monthlySales' => Deal::where('payment_status', 'Paid')
                ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->when($financialScopeUser, fn ($q) => $q->where('sales_user_id', $financialScopeUser))
                ->sum('deal_amount'),
            'pipelineValue' => Opportunity::whereNotIn('stage', ['Done Deal', 'Lost'])
                ->when($crmScopeUser, fn ($q) => $q->where('assigned_sales_id', $crmScopeUser))
                ->sum('estimated_deal_amount'),
        ]);
    })->name('dashboard');

    Route::get('kanban', KanbanController::class)->name('kanban');
    Route::post('kanban/{opportunity}/move', [KanbanController::class, 'move'])->name('kanban.move');

    Route::get('inventory/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('inventory/assets/create', [AssetController::class, 'create'])->name('assets.create');
    Route::post('inventory/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('inventory/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('inventory/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');

    Route::get('opportunities/{opportunity}/trial-agreements/create', [TrialAgreementController::class, 'create'])->name('trial-agreements.create');
    Route::post('trial-agreements', [TrialAgreementController::class, 'store'])->name('trial-agreements.store');
    Route::get('trial-agreements/{trialAgreement}/download', [TrialAgreementController::class, 'download'])->name('trial-agreements.download');
    Route::post('trial-agreements/{trialAgreement}/signed-copy', [TrialAgreementController::class, 'uploadSignedCopy'])->name('trial-agreements.signed-copy');

    Route::post('leads/{lead}/convert', [CrmController::class, 'convertLead'])->name('leads.convert');
    Route::post('opportunities/{opportunity}/stage', [CrmController::class, 'updateOpportunityStage'])->name('opportunities.stage');
    Route::get('{module}', [CrmController::class, 'index'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.index');
    Route::get('{module}/create', [CrmController::class, 'create'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.create');
    Route::post('{module}', [CrmController::class, 'store'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.store');
    Route::post('{module}/{id}/remarks', [CrmController::class, 'updateRemarks'])->whereIn('module', ['leads', 'opportunities'])->name('crm.remarks');
    Route::post('{module}/{id}/comments', [CrmController::class, 'storeComment'])->whereIn('module', ['leads', 'opportunities'])->name('crm.comments.store');
    Route::get('{module}/{id}', [CrmController::class, 'show'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.show');
    Route::get('{module}/{id}/edit', [CrmController::class, 'edit'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.edit');
    Route::put('{module}/{id}', [CrmController::class, 'update'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.update');
    Route::delete('{module}/{id}', [CrmController::class, 'destroy'])->whereIn('module', ['accounts', 'contacts', 'leads', 'opportunities', 'deals'])->name('crm.destroy');

    Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::post('commissions/run', [CommissionController::class, 'run'])->name('commissions.run');
    Route::get('commissions/simulator', [CommissionController::class, 'simulator'])->name('commissions.simulator');
    Route::post('commissions/simulator', [CommissionController::class, 'simulate'])->name('commissions.simulate');
    Route::get('commissions/{commissionRun}', [CommissionController::class, 'show'])->name('commissions.show');
    Route::post('commissions/{commissionRun}/approve', [CommissionController::class, 'approve'])->name('commissions.approve');
    Route::post('commissions/{commissionRun}/override', [CommissionController::class, 'override'])->name('commissions.override');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{type}/export', [ReportController::class, 'export'])->name('reports.export');

    Route::middleware('role:admin')->group(function () {
        Route::get('admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::post('admin/users/{user}/invitation', [UserController::class, 'sendInvitation'])->name('admin.users.invitation');
        Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');

        Route::get('config/{type}', [ConfigController::class, 'index'])->name('config.index');
        Route::post('config/{type}', [ConfigController::class, 'store'])->name('config.store');
        Route::put('config/{type}/{id}', [ConfigController::class, 'update'])->name('config.update');
        Route::delete('config/{type}/{id}', [ConfigController::class, 'destroy'])->name('config.destroy');
    });
});

require __DIR__.'/settings.php';
