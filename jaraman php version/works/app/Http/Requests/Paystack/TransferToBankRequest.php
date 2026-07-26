<?php

namespace App\Http\Requests\Paystack;

use Illuminate\Foundation\Http\FormRequest;

class TransferToBankRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount'   => ['required', 'numeric', 'min:100'], // min ₦100
            'bank_id' =>  ['required', 'exists:bank_accounts,id'],
            'currency' => ['nullable', 'string', 'in:NGN'],   // only NGN for Paystack
            'remark'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
