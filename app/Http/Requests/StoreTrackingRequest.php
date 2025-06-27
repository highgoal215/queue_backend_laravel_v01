<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackingRequest extends FormRequest
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
            'queue_entry_id' => 'required|exists:queue_entries,id',
            'qr_code_url' => 'nullable|url|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'queue_entry_id.required' => 'Queue entry ID is required.',
            'queue_entry_id.exists' => 'The specified queue entry does not exist.',
            'qr_code_url.url' => 'QR code URL must be a valid URL.',
            'qr_code_url.max' => 'QR code URL cannot exceed 2048 characters.',
        ];
    }
} 