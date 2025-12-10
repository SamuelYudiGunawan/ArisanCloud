<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $group = $this->route('group');
        return $group && $group->isMember($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proof_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $group = $this->route('group');
            $activePeriod = $group->activePeriod();

            if (!$activePeriod) {
                $validator->errors()->add('period', 'Tidak ada periode aktif saat ini.');
                return;
            }

            // Check if user already has a payment for this period
            $existingPayment = $activePeriod->payments()
                ->where('user_id', $this->user()->id)
                ->first();

            if ($existingPayment) {
                $validator->errors()->add('payment', 'Anda sudah melakukan pembayaran untuk periode ini. Status: ' . $existingPayment->status);
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'proof_image.required' => 'Bukti pembayaran wajib diupload.',
            'proof_image.image' => 'File harus berupa gambar.',
            'proof_image.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}

