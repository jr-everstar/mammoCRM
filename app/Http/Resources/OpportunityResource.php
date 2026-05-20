<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
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
            'account_id' => $this->account_id,
            'opportunity_name' => $this->opportunity_name,
            'sales_plan_id' => $this->sales_plan_id,
            'estimated_deal_amount' => $this->estimated_deal_amount,
            'probability' => $this->probability,
            'expected_close_date' => $this->expected_close_date,
            'assigned_sales_id' => $this->assigned_sales_id,
            'stage' => $this->stage,
            'lost_reason' => $this->lost_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
