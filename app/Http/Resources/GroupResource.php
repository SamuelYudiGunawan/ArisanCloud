<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'rekening_transfer' => $this->rekening_transfer,
            'period_duration_weeks' => $this->period_duration_weeks,
            'contribution_amount' => $this->contribution_amount,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'member_count' => $this->members->count(),
            'is_creator' => $request->user() && $this->creator_user_id === $request->user()->id,
            'is_complete' => $this->isComplete(),
            'created_at' => $this->created_at,
        ];
    }
}

