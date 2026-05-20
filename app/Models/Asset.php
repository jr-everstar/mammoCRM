<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    public const TYPES = ['ipad', 'sensor', 'charger', 'cable', 'case', 'accessory', 'other'];

    public const SENSOR_SIDES = ['left', 'right'];

    public const STATUSES = ['available', 'reserved', 'on_trial', 'maintenance', 'lost', 'retired'];

    protected $guarded = [];

    public function trialAgreements(): BelongsToMany
    {
        return $this->belongsToMany(TrialAgreement::class, 'trial_agreement_assets')
            ->withPivot(['role', 'condition_at_handover', 'condition_at_return'])
            ->withTimestamps();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function displayName(): string
    {
        return collect([$this->asset_tag, $this->serial_number, $this->model_name])
            ->filter()
            ->implode(' · ');
    }
}
