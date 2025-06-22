<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:regular,inventory',
            'max_quantity' => 'nullable|integer|min:1|required_if:type,inventory',
            'remaining_quantity' => 'nullable|integer|min:0|required_if:type,inventory',
            'status' => 'sometimes|in:active,paused,closed',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Queue name is required.',
            'type.required' => 'Queue type is required.',
            'type.in' => 'Queue type must be either regular or inventory.',
            'max_quantity.required_if' => 'Maximum quantity is required for inventory queues.',
            'max_quantity.integer' => 'Maximum quantity must be a number.',
            'max_quantity.min' => 'Maximum quantity must be at least 1.',
            'remaining_quantity.required_if' => 'Remaining quantity is required for inventory queues.',
            'remaining_quantity.integer' => 'Remaining quantity must be a number.',
            'remaining_quantity.min' => 'Remaining quantity cannot be negative.',
        ];
    }
}
