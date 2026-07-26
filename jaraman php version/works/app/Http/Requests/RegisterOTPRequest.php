<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterOTPRequest extends FormRequest
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
            "otp" => "required|integer|digits:4",
        ];
    }

    public function messages()
    {
        return [
            'otp.required' => 'Please enter the OTP.',
            'otp.integer' => 'OTP must be an integer.',
            'otp.digits' => 'OTP must be exactly 4 digits long.',

        ];
    }
}
