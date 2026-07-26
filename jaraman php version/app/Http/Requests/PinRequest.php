<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PinRequest extends FormRequest
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
            'pin' => 'required|digits:4',

            'confirm_pin' => [
                'nullable',
                Rule::requiredIf(fn () => request()->routeIs('pin.set') || request()->routeIs('pin.reset')),
                'same:pin',
            ],

            'token' => [
                'nullable',
                Rule::requiredIf(fn () => request()->routeIs('pin.reset')),
            ],

            'remember' => 'nullable|boolean',
        ];        
    }
}
