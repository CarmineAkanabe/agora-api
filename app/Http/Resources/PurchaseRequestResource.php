<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
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
            'listing'          => new ListingResource($this->whenLoaded('listing')),
            'buyer'            => new UserResource($this->whenLoaded('buyer')),
            'seller'           => new UserResource($this->whenLoaded('seller')),
            'quantity'         => $this->quantity,
            'total_price'      => $this->total_price,
            'meeting_location' => $this->meeting_location,
            'whatsapp_number'  => $this->whatsapp_number,
            'message'          => $this->message,
            'status'           => $this->status,
            'expires_at'       => $this->expires_at,
            'created_at'       => $this->created_at
        ];
    }
}
