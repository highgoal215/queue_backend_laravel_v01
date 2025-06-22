<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueRequest extends FormRequest
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
            'type' => 'sometimes|in:regular,inventory',
            'status' => 'sometimes|in:active,paused,closed',
            'max_quantity' => 'sometimes|required_if:type,inventory|integer|min:1',
            'remaining_quantity' => 'sometimes|required_if:type,inventory|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Queue name must be a string.',
            'name.max' => 'Queue name cannot exceed 255 characters.',
            'type.in' => 'Queue type must be either regular or inventory.',
            'status.in' => 'Queue status must be active, paused, or closed.',
            'max_quantity.required_if' => 'Maximum quantity is required for inventory queues.',
            'max_quantity.integer' => 'Maximum quantity must be a number.',
            'max_quantity.min' => 'Maximum quantity must be at least 1.',
            'remaining_quantity.required_if' => 'Remaining quantity is required for inventory queues.',
            'remaining_quantity.integer' => 'Remaining quantity must be a number.',
            'remaining_quantity.min' => 'Remaining quantity cannot be negative.',
        ];
    }
} 