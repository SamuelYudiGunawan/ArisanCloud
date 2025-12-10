<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrawHistoryResource extends JsonResource
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
            'period_id' => $this->period_id,
            'period_number' => $this->period->period_number ?? null,
            'winner' => [
                'id' => $this->winner->id,
                'name' => $this->winner->name,
            ],
            'draw_date' => $this->draw_date,
            'total_pot_amount' => $this->total_pot_amount,
        ];
    }
}

