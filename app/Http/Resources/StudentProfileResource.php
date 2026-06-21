<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'matricule'           => $this->matricule,
            'school'              => $this->school,
            'department'          => $this->department,
            'level'               => $this->level,
            'phone'               => $this->phone,
            'whatsapp_number'     => $this->whatsapp_number,
            'profile_picture'     => $this->profile_picture
                                        ? asset('storage/' . $this->profile_picture)
                                        : null,
            'id_card'             => asset('storage/' . $this->id_card_path),
            'verification_status' => $this->verification_status,
            'verified_at'         => $this->verified_at
        ];
    }
}
