<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'transaction' => new TransactionResource($this->whenLoaded('transaction')),
            'raised_by'   => new UserResource($this->whenLoaded('raisedBy')),
            'reason'      => $this->reason,
            'status'      => $this->status,
            'resolution'  => $this->resolution,
            'resolved_by' => new UserResource($this->whenLoaded('resolvedBy')),
            'resolved_at' => $this->resolved_at,
            'created_at'  => $this->created_at,
        ];
    }
}
