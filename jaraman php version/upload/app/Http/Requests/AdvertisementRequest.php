<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementRequest extends FormRequest
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
        return [
            'image'            => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'type'             => 'required|in:discount,off,info',
            'value'            => 'required_if:type,discount,off|numeric|nullable',
            'ingredient_ids'   => 'required_if:type,discount,off|array|nullable',
            'ingredient_ids.*' => 'exists:ingredients,id'
        ];
    }
}
