<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HelpTicketRequest extends FormRequest
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
            'subject' => 'required|string|max:255',
            'message' => 'required|string',

            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx,xlsx',
                'max:5120'
            ],
        ];   
    }
}
