<?php

namespace App\Http\Requests;

use App\Rules\BlockedDomain;
use Illuminate\Validation\Rule;
use App\Enums\UserPermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        $email_rules = ['email:rfc,dns', 'required', new BlockedDomain];

        if ($this->input('role') !== UserPermissionsEnum::VENDOR()) {
            $emailRules[] = Rule::unique('users', 'email')->whereNull('deleted_at');
        }
    
        return [
            "firstname" => "required|string|max:30",
            "lastname" => "required|string|max:30",
            'email' => $email_rules,
            "password" => "required|min:8",
            'referral_code' => 'nullable|string|exists:users,referral_code',
            'phone_number' => ['nullable', Rule::unique('users', 'phone_number')->whereNull('deleted_at')],
            'role' => ['nullable', Rule::in(UserPermissionsEnum::values())],
        ];
    }

    public function messages()
    {
        return [
            'firstname.required' => 'First name is required.',
            'firstname.max' => 'First name cannot be more than 30 characters.',
            
            'lastname.required' => 'Last name is required.',
            'lastname.max' => 'Last name cannot be more than 30 characters.',
            
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already taken.',
            
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            
            'referral_code.exists' => 'The referral code is invalid.',
            
            'phone_number.unique' => 'Phone number is already registered.',
            
            'role.required' => 'User role is required.',
            'role.in' => 'The selected role is invalid.',
        ];
    }
}
