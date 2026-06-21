<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'purchase_request' => new PurchaseRequestResource($this->whenLoaded('purchaseRequest')),
            'buyer'            => new UserResource($this->whenLoaded('buyer')),
            'seller'           => new UserResource($this->whenLoaded('seller')),
            'amount'           => $this->amount,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'pickup_code'      => $this->when(
                $request->user()?->id === $this->buyer_id,
                $this->pickup_code
            ),
            'pickup_code_used_at' => $this->pickup_code_used_at,
            'auto_release_at'     => $this->auto_release_at,
            'created_at'          => $this->created_at
        ];
    }
}
