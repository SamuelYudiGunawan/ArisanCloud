<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
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
            'period_number' => $this->period_number,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'status' => $this->status,
            'all_members_paid' => $this->allMembersPaid(),
            'paid_count' => $this->payments()->where('status', 'approved')->count(),
            'total_members' => $this->group->members()->count(),
        ];
    }
}

