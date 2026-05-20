<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use SoftDeletes;

    public const STAGES = ['Lead-in', 'Meeting / Demo', 'Trial', 'Proposal', 'Negotiation', 'Done Deal', 'Lost'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expected_close_date' => 'date'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function salesPlan(): BelongsTo
    {
        return $this->belongsTo(SalesPlan::class);
    }

    public function assignedSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sales_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OpportunityActivity::class)->latest();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(CrmComment::class, 'commentable')->latest();
    }

    public function deal(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function trialAgreements(): HasMany
    {
        return $this->hasMany(TrialAgreement::class);
    }
}
