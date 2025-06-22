<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashierRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:cashiers,name',
            'employee_id' => 'nullable|string|max:255|unique:cashiers,employee_id',
            'status' => 'sometimes|in:active,inactive,break',
            'assigned_queue_id' => 'nullable|exists:queues,id',
            'is_active' => 'sometimes|boolean',
            'is_available' => 'sometimes|boolean',
            'current_customer_id' => 'nullable|integer',
            'total_served' => 'sometimes|integer|min:0',
            'average_service_time' => 'sometimes|integer|min:0',
            'email' => 'nullable|email|unique:cashiers,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|string|max:100',
            'shift_start' => 'nullable|date_format:H:i',
            'shift_end' => 'nullable|date_format:H:i|after:shift_start',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Cashier name is required.',
            'name.unique' => 'A cashier with this name already exists.',
            'assigned_queue_id.exists' => 'The specified queue does not exist.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'shift_end.after' => 'Shift end time must be after shift start time.',
        ];
    }
}
