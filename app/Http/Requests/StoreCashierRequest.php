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
            'current_customer_id' => 'nullable|integer|exists:queue_entries,id',
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
            'name.string' => 'Cashier name must be a string.',
            'name.max' => 'Cashier name cannot exceed 255 characters.',
            'name.unique' => 'A cashier with this name already exists.',
            'employee_id.unique' => 'A cashier with this employee ID already exists.',
            'assigned_queue_id.exists' => 'The specified queue does not exist.',
            'current_customer_id.exists' => 'The specified customer does not exist.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'shift_start.date_format' => 'Shift start time must be in HH:MM format.',
            'shift_end.date_format' => 'Shift end time must be in HH:MM format.',
            'shift_end.after' => 'Shift end time must be after shift start time.',
            'total_served.integer' => 'Total served must be a number.',
            'total_served.min' => 'Total served cannot be negative.',
            'average_service_time.integer' => 'Average service time must be a number.',
            'average_service_time.min' => 'Average service time cannot be negative.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values for optional fields
        $this->merge([
            'is_active' => $this->is_active ?? true,
            'is_available' => $this->is_available ?? true,
            'status' => $this->status ?? 'active',
            'total_served' => $this->total_served ?? 0,
            'average_service_time' => $this->average_service_time ?? 0,
        ]);
    }
}
