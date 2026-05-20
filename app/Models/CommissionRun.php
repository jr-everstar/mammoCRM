<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'admin_monthly_tier_override' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommissionRunItem::class);
    }
}
