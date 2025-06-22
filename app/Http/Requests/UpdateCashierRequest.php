<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashierRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:cashiers,email,' . $this->route('cashier'),
            'phone' => 'sometimes|string|max:20',
            'is_active' => 'sometimes|boolean',
            'queue_id' => 'sometimes|nullable|exists:queues,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Cashier name must be a string.',
            'name.max' => 'Cashier name cannot exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email cannot exceed 255 characters.',
            'email.unique' => 'This email is already registered.',
            'phone.string' => 'Phone number must be a string.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'is_active.boolean' => 'Active status must be true or false.',
            'queue_id.exists' => 'The specified queue does not exist.',
        ];
    }
} 