<?php

namespace App\Http\Requests;


use App\Enums\CurrencyEnum;
use Illuminate\Validation\Rule;
use App\Enums\PaymentChannelEnum;
use Illuminate\Foundation\Http\FormRequest;

class FundWalletRequest extends FormRequest
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
            'amount'          => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency'        => ['required', 'string', Rule::in(CurrencyEnum::values())],
            'callback_url'    => 'required|string',
            'payment_gateway' => ['required', 'string', Rule::in(PaymentChannelEnum::values())],
            'metadata'        => 'array',
        ];
    }

    public function messages()
    {
        return [        
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a number.',
                    
            'currency.required' => 'The currency is required.',
            'currency.string' => 'The currency must be a valid string.',
            'currency.in' => 'The selected currency is invalid. Allowed values are: ' . implode(', ', CurrencyEnum::values()),
        
            'callback_url.required' => 'The callback URL is required.',
            'callback_url.string' => 'The callback URL must be a valid URL.',
        
            'payment_gateway.required' => 'The payment gateway is required.',
            'payment_gateway.string' => 'The payment gateway must be a string.',
            'payment_gateway.in' => 'The selected payment gateway is invalid. Choose from: ' . implode(', ', PaymentChannelEnum::values()),
        ];
    }
}
