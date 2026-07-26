<?php

namespace App\Http\Requests;

use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class ResetForgottenPasswordRequest extends FormRequest
{
    public ?User $user = null;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'recipient' => [
                'required',
                'string',
                filter_var($this->recipient, FILTER_VALIDATE_EMAIL) ? 'email:rfc,dns' : '',
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->user = User::where('email', $this->recipient)
                ->orWhere('phone_number', $this->recipient)
                ->first();

            if (! $this->user) {
                $validator->errors()->add('recipient', 'We could not find a user with this email or phone.');
                return;
            }

            if (Hash::check($this->password, $this->user->password)) {
                $validator->errors()->add('password', 'Old password and new password cannot be the same.');
            }
        });
    }
}
