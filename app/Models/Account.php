<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'company_registration_number', 'company_registration_number');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AccountDocument::class);
    }

    public function quotations(): HasMany
    {
        return $this->documents()->where('type', 'quotation');
    }

    public function contracts(): HasMany
    {
        return $this->documents()->where('type', 'contract');
    }

    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    public function trialAgreements(): HasMany
    {
        return $this->hasMany(TrialAgreement::class);
    }

    public function trialAgreementDocuments(): HasMany
    {
        return $this->documents()->where('type', 'trial_agreement');
    }
}
