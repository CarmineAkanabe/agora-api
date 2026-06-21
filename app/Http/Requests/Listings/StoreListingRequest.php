<?php

namespace App\Http\Requests\Listings;

use App\Enums\ListingCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
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
            'category_id'  => ['required', 'exists:categories,id'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'price'        => ['required', 'numeric', 'min:1'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'condition'    => ['required', Rule::enum(ListingCondition::class)],
            'images'       => ['required', 'array', 'min:1', 'max:5'],
            'images.*'     => ['image', 'max:2048'],
            'primary_image'=> ['required', 'integer', 'min:0', 'max:4']
        ];
    }
}
