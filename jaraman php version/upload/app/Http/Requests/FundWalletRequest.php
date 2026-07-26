<?php

namespace App\Http\Requests;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentChannelEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FundWalletRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'amount'          => ['required', 'numeric', 'min:100'],
            'currency'        => ['required', 'string', Rule::in(CurrencyEnum::values())],
            'callback_url'    => ['required', 'url'],
            'payment_gateway' => ['required', 'string', Rule::in(PaymentChannelEnum::values())],
            'metadata'        => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'         => 'Minimum funding amount is ₦100.',
            'currency.in'        => 'Unsupported currency. Allowed: ' . implode(', ', CurrencyEnum::values()),
            'callback_url.url'   => 'The callback URL must be a valid URL.',
            'payment_gateway.in' => 'Unsupported gateway. Allowed: ' . implode(', ', PaymentChannelEnum::values()),
        ];
    }
}
