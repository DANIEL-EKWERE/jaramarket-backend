<?php

namespace App\Http\Requests;


use Illuminate\Validation\Rule;
use App\Enums\DeliveryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $deliveryType = $this->input('delivery_type');

        return [
            'order_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'delivery_type' => ['required', Rule::in(DeliveryTypeEnum::values())],
            'shipping_fee'  => [
                'nullable',
                'numeric',
                Rule::requiredIf($deliveryType === DeliveryTypeEnum::PICKUP()),
            ],
            'service_charge' => ['required', 'numeric'],
            'vat' => ['nullable', 'numeric'],
            'total' => ['required', 'numeric'],
            'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'remarks' => 'nullable',
            'address_id' => [
                'nullable',
                'exists:addresses,id',
                Rule::requiredIf($deliveryType === DeliveryTypeEnum::PICKUP()),
            ],
            
            'products' => ['nullable', 'array', 'required_without:ingredients'],
            'products.*.product_id' => ['nullable', 'exists:products,id'],
            'products.*.price' => ['nullable', 'numeric'],
            'products.*.quantity' => ['nullable', 'numeric'],

            'ingredients' => ['nullable', 'array', 'required_without:products'],
            'ingredients.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'ingredients.*.price' => ['nullable', 'numeric'],
            'ingredients.*.quantity' => ['required', 'numeric'],
            'ingredients.*.unit' => ['required', 'exists:uoms,code'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Please provide a valid date for the appointment.',
            'address_id.required' => 'The address field is required when pickup is selected.',
            'products.required_without' => 'You must select at least one product or ingredient.',
            'ingredients.required_without' => 'You must select at least one product or ingredient.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['products', 'ingredients'] as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $decoded = json_decode($data[$field], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([
                        $field => $decoded,
                    ]);
                }
            }
        }
    }
}
