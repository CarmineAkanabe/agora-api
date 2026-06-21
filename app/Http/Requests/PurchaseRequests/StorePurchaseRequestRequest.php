<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
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
        return [
            'listing_id'       => ['required', 'exists:listings,id'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'meeting_location' => ['required', 'string', 'max:255'],
            'whatsapp_number'  => ['required', 'string'],
            'message'          => ['nullable', 'string', 'max:500']
        ];
    }
}
