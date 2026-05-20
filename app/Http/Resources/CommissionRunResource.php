<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionRunResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->month,
            'sales_user_id' => $this->sales_user_id,
            'status' => $this->status,
            'monthly_qualified_sales_amount' => $this->monthly_qualified_sales_amount,
            'basic_commission' => $this->basic_commission,
            'renewal_upgrade_commission' => $this->renewal_upgrade_commission,
            'monthly_tier_bonus' => $this->monthly_tier_bonus,
            'high_plan_accelerator_bonus' => $this->high_plan_accelerator_bonus,
            'total_commission' => $this->total_commission,
            'pre_commission_gross_margin' => $this->pre_commission_gross_margin,
            'post_commission_remaining_gross_margin' => $this->post_commission_remaining_gross_margin,
            'incentive_ratio' => $this->incentive_ratio,
            'override_total_commission' => $this->override_total_commission,
            'override_reason' => $this->override_reason,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
