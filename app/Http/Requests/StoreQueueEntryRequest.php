<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueEntryRequest extends FormRequest
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
            'queue_id' => 'required|exists:queues,id',
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'order_details' => 'nullable|string',
            'quantity_purchased' => 'nullable|integer|min:1',
            'estimated_wait_time' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'cashier_id' => 'nullable|exists:cashiers,id',
            'order_status' => 'sometimes|in:queued,kitchen,preparing,serving,completed,cancelled',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'queue_id.required' => 'Queue ID is required.',
            'queue_id.exists' => 'The specified queue does not exist.',
            'customer_name.required' => 'Customer name is required.',
            'customer_name.string' => 'Customer name must be a string.',
            'customer_name.max' => 'Customer name must not exceed 255 characters.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.string' => 'Phone number must be a string.',
            'phone_number.max' => 'Phone number must not exceed 20 characters.',
            'order_details.string' => 'Order details must be a string.',
            'quantity_purchased.integer' => 'Quantity purchased must be a number.',
            'quantity_purchased.min' => 'Quantity purchased must be at least 1.',
            'estimated_wait_time.integer' => 'Estimated wait time must be a number.',
            'estimated_wait_time.min' => 'Estimated wait time must be at least 1.',
            'notes.string' => 'Notes must be a string.',
            'cashier_id.exists' => 'The specified cashier does not exist.',
            'order_status.in' => 'Invalid order status.',
        ];
    }
}