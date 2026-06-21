<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
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
            'title'       => $this->title,
            'description' => $this->description,
            'price'       => $this->price,
            'quantity'    => $this->quantity,
            'condition'   => $this->condition,
            'status'      => $this->status,
            'category'    => new CategoryResource($this->whenLoaded('category')),
            'seller'      => new UserResource($this->whenLoaded('seller')),
            'images'      => ListingImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new ListingImageResource($this->whenLoaded('primaryImage')),
            'created_at'  => $this->created_at
        ];
    }
}
