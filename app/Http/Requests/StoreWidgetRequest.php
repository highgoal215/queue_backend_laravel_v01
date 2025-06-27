<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWidgetRequest extends FormRequest
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
            'screen_layout_id' => 'required|exists:screen_layouts,id',
            'type' => 'required|string|in:time,date,weather,queue,announcement,custom',
            'position' => 'required|array',
            'position.x' => 'required|integer|min:0',
            'position.y' => 'required|integer|min:0',
            'position.width' => 'required|integer|min:1',
            'position.height' => 'required|integer|min:1',
            'settings' => 'array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'screen_layout_id.required' => 'Screen layout ID is required.',
            'screen_layout_id.exists' => 'The specified screen layout does not exist.',
            'type.required' => 'Widget type is required.',
            'type.string' => 'Widget type must be a string.',
            'type.in' => 'Widget type must be one of: time, date, weather, queue, announcement, custom.',
            'position.required' => 'Widget position is required.',
            'position.array' => 'Widget position must be an array.',
            'position.x.required' => 'Widget X position is required.',
            'position.x.integer' => 'Widget X position must be a number.',
            'position.x.min' => 'Widget X position cannot be negative.',
            'position.y.required' => 'Widget Y position is required.',
            'position.y.integer' => 'Widget Y position must be a number.',
            'position.y.min' => 'Widget Y position cannot be negative.',
            'position.width.required' => 'Widget width is required.',
            'position.width.integer' => 'Widget width must be a number.',
            'position.width.min' => 'Widget width must be at least 1.',
            'position.height.required' => 'Widget height is required.',
            'position.height.integer' => 'Widget height must be a number.',
            'position.height.min' => 'Widget height must be at least 1.',
            'settings.array' => 'Widget settings must be an array.',
        ];
    }
}
