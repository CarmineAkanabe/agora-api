<?php

namespace App\Http\Requests\Reviews;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'transaction_id' => ['required', 'exists:transactions,id'],
            'rating'         => ['required', 'integer', 'min:1', 'max:5'],
            'comment'        => ['nullable', 'string', 'max:500']
        ];
    }
}
