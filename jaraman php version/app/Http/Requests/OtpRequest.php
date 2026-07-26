<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class OtpRequest extends FormRequest
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
            'otp' => [
                'required',
                'integer',
                'digits:4',
                Rule::exists('user_otps', 'otp'),
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                Rule::exists('user_otps', 'email'),
            ],
        ];
    }

    public function messages()
    {
        return [
            'otp.required' => 'OTP is required.',
            'otp.integer' => 'OTP must be a number.',
            'otp.digits' => 'OTP must be exactly 4 digits.',
            'otp.exists' => 'This OTP is invalid or has expired.',
        
            'email.required' => 'Email is required.',
            'email.string' => 'Email must be a valid string.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'No OTP found for this email.',
        ];
    }
}
