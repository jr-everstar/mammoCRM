<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalUpgradeRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'can_trigger_monthly_tier' => 'boolean'];
    }
}
