<?php

namespace App\Http\Requests\Listings;

use App\Enums\ListingCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
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
            'category_id'  => ['sometimes', 'exists:categories,id'],
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['sometimes', 'string'],
            'price'        => ['sometimes', 'numeric', 'min:1'],
            'quantity'     => ['sometimes', 'integer', 'min:1'],
            'condition'    => ['sometimes', Rule::enum(ListingCondition::class)],
            'images'       => ['sometimes', 'array', 'min:1', 'max:5'],
            'images.*'     => ['image', 'max:2048'],
            'primary_image'=> ['sometimes', 'integer', 'min:0']
        ];
    }
}
