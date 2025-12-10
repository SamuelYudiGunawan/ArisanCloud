<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activePeriod = $this->activePeriod();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'rekening_transfer' => $this->rekening_transfer,
            'period_duration_weeks' => $this->period_duration_weeks,
            'contribution_amount' => $this->contribution_amount,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ],
            'is_creator' => $request->user() && $this->creator_user_id === $request->user()->id,
            'is_complete' => $this->isComplete(),
            'members' => MemberResource::collection($this->members),
            'active_period' => $activePeriod ? new PeriodResource($activePeriod) : null,
            'current_period_payment_status' => $activePeriod ? $activePeriod->memberPaymentStatus() : [],
            'draw_history' => DrawHistoryResource::collection($this->draws()->with('winner')->orderBy('draw_date', 'desc')->get()),
            'total_periods' => $this->periods->count(),
            'total_draws' => $this->draws->count(),
            'created_at' => $this->created_at,
        ];
    }
}

