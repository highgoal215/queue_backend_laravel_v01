<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
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
            'order_status' => 'required|in:queued,kitchen,preparing,serving,completed,cancelled',
            'notes' => 'nullable|string|max:500',
            'estimated_completion_time' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_status.required' => 'Order status is required.',
            'order_status.in' => 'Order status must be one of: queued, kitchen, preparing, serving, completed, cancelled.',
            'notes.string' => 'Notes must be a string.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
            'estimated_completion_time.date' => 'Estimated completion time must be a valid date.',
            'estimated_completion_time.after' => 'Estimated completion time must be in the future.',
        ];
    }
} 