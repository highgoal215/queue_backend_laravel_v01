<?php

namespace App\Services;

use App\Models\ScreenLayout;
use App\Models\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LayoutService
{
    /**
     * Get all layouts with optional filters
     */
    public function getAllLayouts(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = ScreenLayout::query();

        if (isset($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }

        return $query->with('widgets')->get();
    }

    /**
     * Create a new layout
     */
    public function createLayout(array $data): ScreenLayout
    {
        DB::beginTransaction();
        try {
            // If this is set as default, unset other defaults for this device
            if (isset($data['is_default']) && $data['is_default']) {
                ScreenLayout::where('device_id', $data['device_id'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $layout = ScreenLayout::create([
                'name' => $data['name'],
                'device_id' => $data['device_id'],
                'layout_config' => $data['layout_config'],
                'is_default' => $data['is_default'] ?? false,
            ]);

            // Create widgets if provided
            if (isset($data['layout_config']['widgets'])) {
                foreach ($data['layout_config']['widgets'] as $widgetData) {
                    Widget::create([
                        'screen_layout_id' => $layout->id,
                        'type' => $widgetData['type'],
                        'position' => json_encode($widgetData['position']),
                        'settings_json' => $widgetData['settings'] ?? null,
                    ]);
                }
            }

            DB::commit();
            $layout->load('widgets');

            return $layout;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create layout: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing layout
     */
    public function updateLayout(ScreenLayout $layout, array $data): ScreenLayout
    {
        DB::beginTransaction();
        try {
            // If this is set as default, unset other defaults for this device
            if (isset($data['is_default']) && $data['is_default']) {
                ScreenLayout::where('device_id', $layout->device_id)
                    ->where('id', '!=', $layout->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $layout->update(array_filter($data, function ($key) {
                return in_array($key, ['name', 'layout_config', 'is_default']);
            }, ARRAY_FILTER_USE_KEY));

            // Update widgets if provided
            if (isset($data['layout_config']['widgets'])) {
                // Delete existing widgets
                $layout->widgets()->delete();

                // Create new widgets
                foreach ($data['layout_config']['widgets'] as $widgetData) {
                    Widget::create([
                        'screen_layout_id' => $layout->id,
                        'type' => $widgetData['type'],
                        'position' => json_encode($widgetData['position']),
                        'settings_json' => $widgetData['settings'] ?? null,
                    ]);
                }
            }

            DB::commit();
            $layout->load('widgets');

            return $layout;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update layout: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a layout
     */
    public function deleteLayout(ScreenLayout $layout): bool
    {
        try {
            // Check if this is the default layout for the device
            if ($layout->is_default) {
                throw new \Exception('Cannot delete default layout. Set another layout as default first.');
            }

            $layout->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete layout: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get layout by device ID (default layout)
     */
    public function getLayoutByDevice(string $deviceId): ?ScreenLayout
    {
        return ScreenLayout::where('device_id', $deviceId)
            ->where('is_default', true)
            ->with('widgets')
            ->first();
    }

    /**
     * Set a layout as default for its device
     */
    public function setAsDefault(ScreenLayout $layout): ScreenLayout
    {
        DB::beginTransaction();
        try {
            // Unset other defaults for this device
            ScreenLayout::where('device_id', $layout->device_id)
                ->where('id', '!=', $layout->id)
                ->update(['is_default' => false]);

            // Set this layout as default
            $layout->update(['is_default' => true]);

            DB::commit();
            $layout->load('widgets');

            return $layout;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to set layout as default: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Duplicate a layout
     */
    public function duplicateLayout(ScreenLayout $layout): ScreenLayout
    {
        DB::beginTransaction();
        try {
            $newLayout = $layout->replicate();
            $newLayout->name = $layout->name . ' (Copy)';
            $newLayout->is_default = false;
            $newLayout->save();

            // Duplicate widgets
            foreach ($layout->widgets as $widget) {
                $newWidget = $widget->replicate();
                $newWidget->screen_layout_id = $newLayout->id;
                $newWidget->save();
            }

            DB::commit();
            $newLayout->load('widgets');

            return $newLayout;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate layout: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get preview data for a layout
     */
    public function getPreviewData(ScreenLayout $layout): array
    {
        $layout->load('widgets');

        $previewData = [];
        foreach ($layout->widgets as $widget) {
            $previewData[] = [
                'id' => $widget->id,
                'type' => $widget->type,
                'position' => json_decode($widget->position, true),
                'settings' => $widget->settings_json,
                'preview_data' => $this->generateWidgetPreviewData($widget)
            ];
        }

        return [
            'layout' => $layout,
            'preview_data' => $previewData
        ];
    }

    /**
     * Generate preview data for widgets
     */
    private function generateWidgetPreviewData(Widget $widget): array
    {
        switch ($widget->type) {
            case 'time':
                return [
                    'current_time' => now()->format('H:i:s'),
                    'timezone' => config('app.timezone')
                ];
            
            case 'date':
                return [
                    'current_date' => now()->format('l, F j, Y'),
                    'day_of_week' => now()->format('l')
                ];
            
            case 'weather':
                return [
                    'temperature' => '22°C',
                    'condition' => 'Sunny',
                    'location' => 'New York'
                ];
            
            case 'queue':
                return [
                    'current_number' => 'A001',
                    'estimated_wait' => '5 minutes',
                    'total_in_queue' => 12
                ];
            
            case 'announcement':
                return [
                    'message' => 'Welcome to our service!',
                    'type' => 'info'
                ];
            
            default:
                return [];
        }
    }

    /**
     * Get layouts by device with pagination
     */
    public function getLayoutsByDevice(string $deviceId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ScreenLayout::where('device_id', $deviceId)
            ->with('widgets')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get layout statistics
     */
    public function getLayoutStats(): array
    {
        $totalLayouts = ScreenLayout::count();
        $defaultLayouts = ScreenLayout::where('is_default', true)->count();
        $layoutsWithWidgets = ScreenLayout::has('widgets')->count();
        
        $widgetStats = Widget::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total_layouts' => $totalLayouts,
            'default_layouts' => $defaultLayouts,
            'layouts_with_widgets' => $layoutsWithWidgets,
            'widget_distribution' => $widgetStats,
            'average_widgets_per_layout' => $totalLayouts > 0 ? 
                round(Widget::count() / $totalLayouts, 2) : 0
        ];
    }
}
