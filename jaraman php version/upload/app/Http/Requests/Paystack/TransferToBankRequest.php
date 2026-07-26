<?php

namespace App\Http\Requests\Paystack;

use Illuminate\Foundation\Http\FormRequest;

class TransferToBankRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'amount'   => ['required', 'numeric', 'min:100'],
            'bank_id'  => ['required', 'exists:bank_accounts,id'],
            'currency' => ['nullable', 'string', 'in:NGN'],
            'remark'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'     => 'Minimum withdrawal amount is ₦100.',
            'bank_id.exists' => 'The selected bank account does not exist.',
        ];
    }
}
