<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'application_id' => $this->id,
            'customer_name' => $this->customer->full_name,
            'address' => $this->full_address,
            'state' => $this->state,
            'plan_type' => $this->plan->type,
            'plan_name' => $this->plan->name,
            'plan_monthly_cost' => $this->plan->monthly_cost_formatted,
            'order_id' => $this->when($this->status === ApplicationStatus::Complete, $this->order_id),
        ];
    }
}