<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', filter_var(request()->email, FILTER_VALIDATE_EMAIL) ? 'email:rfc,dns' : ''],
            "password" => "required|min:8",
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'An email address is required to register.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address cannot exceed 255 characters.',
            'password.required' => 'A password is required.',
            'password.min' => 'Your password must be at least 8 characters long.',
        ];
    }
}
