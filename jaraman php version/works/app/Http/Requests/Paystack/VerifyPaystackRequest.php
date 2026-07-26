<?php

namespace App\Http\Requests\Paystack;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaystackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return $this->hasHeader('X-Paystack-Signature') && $this->header('X-Paystack-Signature') == hash_hmac('sha512', json_encode($this->all()), config("app.paystack_secret_key"));

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

        ];
    }
}
