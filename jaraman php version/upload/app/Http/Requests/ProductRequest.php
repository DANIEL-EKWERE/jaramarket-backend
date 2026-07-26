<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core product fields
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'preparation_steps'=> ['nullable', 'string'],
            'price'            => ['required', 'numeric', 'min:0'],
            'discount_price'   => ['nullable', 'numeric', 'min:0'],
            'rating'           => ['nullable', 'numeric', 'min:0', 'max:5'],
            'stock'            => ['nullable', 'integer', 'min:0'],
            'image_url'        => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Relations
            'categories'       => ['nullable', 'array'],
            'categories.*'     => ['exists:categories,id'],

            'ingredients'                        => ['nullable', 'array'],
            'ingredients.*.ingredient_id'        => ['nullable', 'exists:ingredients,id'],
            'ingredients.*.quantity'             => ['nullable', 'numeric', 'min:0'],
            'ingredients.*.unit'                 => ['nullable', 'string'],

            // State-based pricing
            'state_prices'                       => ['nullable', 'array'],
            'state_prices.*.state_id'            => ['required_with:state_prices.*', 'exists:states,id'],
            'state_prices.*.price'               => ['required_with:state_prices.*', 'numeric', 'min:0'],
            'state_prices.*.discount_price'      => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'state_prices.*.state_id.required_with' => 'Each state price entry must have a state selected.',
            'state_prices.*.price.required_with'    => 'Each state price entry must have a price.',
            'state_prices.*.state_id.exists'        => 'The selected state is invalid.',
        ];
    }
}
