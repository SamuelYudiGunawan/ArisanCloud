<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rekening_transfer' => ['nullable', 'string', 'max:255'],
            'period_duration_weeks' => ['sometimes', 'required', 'integer', 'min:1'],
            'contribution_amount' => ['sometimes', 'required', 'integer', 'min:1000'],
        ];
    }
}

