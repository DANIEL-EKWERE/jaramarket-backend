<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\UserPermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

class DecideOrderItemRequest extends FormRequest
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
        $rules = [
            'status' => ['required', Rule::in(['accepted', 'rejected','completed'])],
        ];

        if (auth()->user()->role === UserPermissionsEnum::ADMIN()) {
            $rules['vendor_id'] = ['required', 'exists:users,id'];
        }

        return $rules;
    }
}
