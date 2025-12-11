<?php

namespace App\Http\Requests;

use App\Models\ArisanGroup;
use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('group');
        return $group && $group->isCreator($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $group = $this->route('group');

            // Check if user is already a member
            $user = \App\Models\User::where('email', $this->email)->first();
            if ($user && $group->isMember($user)) {
                $validator->errors()->add('email', 'User sudah menjadi anggota grup ini.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'User dengan email tersebut tidak ditemukan.',
        ];
    }
}

