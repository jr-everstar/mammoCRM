<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'opportunity_id' => $this->opportunity_id,
            'sales_user_id' => $this->sales_user_id,
            'account_manager_id' => $this->account_manager_id,
            'sales_plan_id' => $this->sales_plan_id,
            'deal_type' => $this->deal_type,
            'deal_amount' => $this->deal_amount,
            'payment_status' => $this->payment_status,
            'payment_date' => $this->payment_date,
            'contract_date' => $this->contract_date,
            'commission_status' => $this->commission_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
