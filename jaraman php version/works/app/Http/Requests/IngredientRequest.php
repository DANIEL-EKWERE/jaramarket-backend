<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'price'            => ['required', 'numeric', 'min:0'],
            'discounted_price' => ['nullable', 'numeric', 'min:0'],
            'unit'             => ['required', 'string', 'exists:uoms,code'],
            'stock'            => ['nullable', 'integer', 'min:0'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'image_url'        => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // State-based pricing
            'state_prices'                    => ['nullable', 'array'],
            'state_prices.*.state_id'         => ['required_with:state_prices.*', 'exists:states,id'],
            'state_prices.*.price'            => ['required_with:state_prices.*', 'numeric', 'min:0'],
            'state_prices.*.discounted_price' => ['nullable', 'numeric', 'min:0'],

            // LGA-based pricing
            'lga_prices'                      => ['nullable', 'array'],
            'lga_prices.*.lga_id'             => ['required_with:lga_prices.*', 'exists:lgas,id'],
            'lga_prices.*.price'              => ['required_with:lga_prices.*', 'numeric', 'min:0'],
            'lga_prices.*.discounted_price'   => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'state_prices.*.state_id.required_with' => 'Each state price entry must have a state selected.',
            'state_prices.*.price.required_with'    => 'Each state price entry must have a price.',
            'state_prices.*.state_id.exists'        => 'The selected state is invalid.',
            'lga_prices.*.lga_id.required_with'     => 'Each LGA price entry must have an LGA selected.',
            'lga_prices.*.price.required_with'      => 'Each LGA price entry must have a price.',
            'lga_prices.*.lga_id.exists'            => 'The selected LGA is invalid.',
            'unit.exists'                           => 'The selected unit is invalid.',
        ];
    }
}
