<?php

namespace App\Http\Requests;


use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CommissionRequest extends FormRequest
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
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
