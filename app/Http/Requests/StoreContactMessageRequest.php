<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'], 'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:10000']];
    }
}
