<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'period_id' => $this->period_id,
            'period_number' => $this->period->period_number ?? null,
            'amount_paid' => $this->amount_paid,
            'payment_date' => $this->payment_date,
            'status' => $this->status,
            'proof_image' => $this->proof_image ? asset('storage/' . $this->proof_image) : null,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}

