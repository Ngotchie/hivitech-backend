<?php

namespace App\Http\Requests;

use App\Enums\PreferredContactMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:20', 'max:10000'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'desired_deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_contact_method' => ['nullable', Rule::enum(PreferredContactMethod::class)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp,txt'],
        ];
    }
}
