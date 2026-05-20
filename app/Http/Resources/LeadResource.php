<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
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
            'lead_name' => $this->lead_name,
            'company_name' => $this->company_name,
            'company_registration_number' => $this->company_registration_number,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'source' => $this->source,
            'business_type' => $this->business_type,
            'assigned_sales_id' => $this->assigned_sales_id,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'converted_account_id' => $this->converted_account_id,
            'converted_opportunity_id' => $this->converted_opportunity_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
