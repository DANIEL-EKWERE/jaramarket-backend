<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                Rule::exists('users', 'email'),
            ],
        ];
    }

    public function messages()
    {
        return [        
            'email.required' => 'Email is required.',
            'email.string' => 'Email must be a valid string.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'Invalid email.',
        ];
    }
}
