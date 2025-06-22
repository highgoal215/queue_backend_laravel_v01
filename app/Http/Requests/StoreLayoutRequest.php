<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLayoutRequest extends FormRequest
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
            'device_id' => 'required|string|max:255',
            'layout_config' => 'required|array',
            'layout_config.grid' => 'required|array',
            'layout_config.grid.columns' => 'required|integer|min:1|max:12',
            'layout_config.grid.rows' => 'required|integer|min:1|max:12',
            'layout_config.widgets' => 'array',
            'layout_config.widgets.*.type' => 'required|string|in:time,date,weather,queue,announcement,custom',
            'layout_config.widgets.*.position' => 'required|array',
            'layout_config.widgets.*.position.x' => 'required|integer|min:0',
            'layout_config.widgets.*.position.y' => 'required|integer|min:0',
            'layout_config.widgets.*.position.width' => 'required|integer|min:1',
            'layout_config.widgets.*.position.height' => 'required|integer|min:1',
            'layout_config.widgets.*.settings' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Layout name is required.',
            'name.string' => 'Layout name must be a string.',
            'name.max' => 'Layout name cannot exceed 255 characters.',
            'device_id.required' => 'Device ID is required.',
            'device_id.string' => 'Device ID must be a string.',
            'device_id.max' => 'Device ID cannot exceed 255 characters.',
            'layout_config.required' => 'Layout configuration is required.',
            'layout_config.array' => 'Layout configuration must be an array.',
            'layout_config.grid.required' => 'Grid configuration is required.',
            'layout_config.grid.array' => 'Grid configuration must be an array.',
            'layout_config.grid.columns.required' => 'Grid columns are required.',
            'layout_config.grid.columns.integer' => 'Grid columns must be a number.',
            'layout_config.grid.columns.min' => 'Grid columns must be at least 1.',
            'layout_config.grid.columns.max' => 'Grid columns cannot exceed 12.',
            'layout_config.grid.rows.required' => 'Grid rows are required.',
            'layout_config.grid.rows.integer' => 'Grid rows must be a number.',
            'layout_config.grid.rows.min' => 'Grid rows must be at least 1.',
            'layout_config.grid.rows.max' => 'Grid rows cannot exceed 12.',
            'layout_config.widgets.array' => 'Widgets must be an array.',
            'layout_config.widgets.*.type.required' => 'Widget type is required.',
            'layout_config.widgets.*.type.in' => 'Widget type must be one of: time, date, weather, queue, announcement, custom.',
            'layout_config.widgets.*.position.required' => 'Widget position is required.',
            'layout_config.widgets.*.position.array' => 'Widget position must be an array.',
            'layout_config.widgets.*.position.x.required' => 'Widget X position is required.',
            'layout_config.widgets.*.position.x.integer' => 'Widget X position must be a number.',
            'layout_config.widgets.*.position.x.min' => 'Widget X position cannot be negative.',
            'layout_config.widgets.*.position.y.required' => 'Widget Y position is required.',
            'layout_config.widgets.*.position.y.integer' => 'Widget Y position must be a number.',
            'layout_config.widgets.*.position.y.min' => 'Widget Y position cannot be negative.',
            'layout_config.widgets.*.position.width.required' => 'Widget width is required.',
            'layout_config.widgets.*.position.width.integer' => 'Widget width must be a number.',
            'layout_config.widgets.*.position.width.min' => 'Widget width must be at least 1.',
            'layout_config.widgets.*.position.height.required' => 'Widget height is required.',
            'layout_config.widgets.*.position.height.integer' => 'Widget height must be a number.',
            'layout_config.widgets.*.position.height.min' => 'Widget height must be at least 1.',
            'layout_config.widgets.*.settings.array' => 'Widget settings must be an array.',
            'is_default.boolean' => 'Default flag must be true or false.',
        ];
    }
}
