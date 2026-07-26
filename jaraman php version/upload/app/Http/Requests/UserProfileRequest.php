<?php

namespace App\Http\Requests;

use App\Enums\UserPermissionsEnum;
use App\Models\User;
use App\Enums\ShopSizeEnum;
use Illuminate\Validation\Rule;
use App\Enums\PaymentMethodEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UserProfileRequest extends FormRequest
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
    public function rules()
    {
        $authUser = $this->user(); // may be null

        // Get email from route
        $routeEmail = $this->route('email');

        // Resolve the user being updated
        $targetUser = $routeEmail
            ? User::where('email', $routeEmail)->first()
            : null;

        $phoneRule = ['nullable', 'string'];

        /*
        |--------------------------------------------------------------------------
        | Apply unique rule ONLY when:
        | - requester exists
        | - requester is NOT admin
        |--------------------------------------------------------------------------
        */
        if ($authUser && $authUser->role !== UserPermissionsEnum::ADMIN()) {
            $phoneRule[] = Rule::unique('users', 'phone_number')
                ->ignore($targetUser?->id ?? $authUser->id)
                ->whereNull('deleted_at');
        }

        return [
            "firstname"    => "nullable|string|max:30",
            "lastname"     => "nullable|string|max:30",
            'phone_number' => $phoneRule,

            'country_id'   => ['nullable', 'exists:countries,id'],
            'profile_picture'  => 'nullable|mimes:jpg,jpeg,png|max:2048',

            'business_name'    => 'nullable|string|max:255',
            'business_address' => 'nullable|string|max:255',
            'shop_size'        => ['nullable', Rule::in(ShopSizeEnum::values())],

            'latitude'         => 'nullable',
            'longitude'        => 'nullable',

            'bank_id'          => 'nullable|exists:banks,id',
            'account_number'   => 'nullable',
            'account_name'     => 'nullable',

            'payment_method'   => ['nullable', new Enum(PaymentMethodEnum::class)],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'firstname.required' => 'First name is required.',
            'firstname.max' => 'First name cannot be more than 30 characters.',
            
            'lastname.required' => 'Last name is required.',
            'lastname.max' => 'Last name cannot be more than 30 characters.',
                        
            'phone_number.unique' => 'Phone number is already registered.',
        ];
    }
}
