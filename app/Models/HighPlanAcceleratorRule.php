<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HighPlanAcceleratorRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function salesPlanRequirements(): BelongsToMany
    {
        return $this->belongsToMany(SalesPlan::class)
            ->withPivot('required_quantity')
            ->withTimestamps();
    }
}
