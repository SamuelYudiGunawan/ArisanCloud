<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rekening_transfer' => ['nullable', 'string', 'max:255'],
            'period_duration_weeks' => ['required', 'integer', 'min:1'],
            'contribution_amount' => ['required', 'integer', 'min:1000'],
            'member_emails' => ['nullable', 'array'],
            'member_emails.*' => ['email', 'exists:users,email'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'member_emails.*.exists' => 'User dengan email :input tidak ditemukan.',
            'contribution_amount.min' => 'Jumlah iuran minimal Rp 1.000.',
        ];
    }
}

