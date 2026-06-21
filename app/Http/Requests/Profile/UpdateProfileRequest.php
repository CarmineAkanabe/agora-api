<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profileId = $this->user()->studentProfile?->id;

        return [
            'matricule'       => [
                'required', 'string',
                Rule::unique('student_profiles', 'matricule')->ignore($profileId)
            ],
            'school'          => ['required', 'string'],
            'department'      => ['required', 'string'],
            'level'           => ['required', 'string'],
            'phone'           => ['required', 'string'],
            'whatsapp_number' => ['required', 'string'],
            'id_card'         => ['required_without:id_card_path', 'image', 'max:2048'],
            'profile_picture' => ['nullable', 'image', 'max:1024']
            ];
    }
}
