<?php

namespace App\Services;

use App\Models\Widget;
use App\Models\ScreenLayout;
use App\Models\Queue;
use Illuminate\Support\Facades\Log;

class WidgetService
{
    /**
     * Get widget data for a specific device
     */
    public function getWidgetData(string $deviceId, array $widgetTypes = []): array
    {
        try {
            // Get the default layout for the device
            $layout = ScreenLayout::where('device_id', $deviceId)
                ->where('is_default', true)
                ->with('widgets')
                ->first();

            if (!$layout) {
                return [
                    'device_id' => $deviceId,
                    'layout' => null,
                    'widgets' => []
                ];
            }

            $widgets = $layout->widgets;

            // Filter by widget types if specified
            if (!empty($widgetTypes)) {
                $widgets = $widgets->whereIn('type', $widgetTypes);
            }

            $widgetData = [];
            foreach ($widgets as $widget) {
                $widgetData[] = [
                    'id' => $widget->id,
                    'type' => $widget->type,
                    'position' => json_decode($widget->position, true),
                    'settings' => $widget->settings,
                    'data' => $this->generateWidgetData($widget)
                ];
            }

            return [
                'device_id' => $deviceId,
                'layout' => [
                    'id' => $layout->id,
                    'name' => $layout->name,
                    'grid' => $layout->layout_config['grid'] ?? null
                ],
                'widgets' => $widgetData
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get widget data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get widgets for a specific layout
     */
    public function getWidgetsByLayout(ScreenLayout $layout): array
    {
        try {
            $widgets = $layout->widgets()->get();
            
            $widgetData = [];
            foreach ($widgets as $widget) {
                $widgetData[] = [
                    'id' => $widget->id,
                    'type' => $widget->type,
                    'position' => json_decode($widget->position, true),
                    'settings' => $widget->settings,
                    'data' => $this->generateWidgetData($widget)
                ];
            }

            return $widgetData;
        } catch (\Exception $e) {
            Log::error('Failed to get widgets by layout: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get widgets by type
     */
    public function getWidgetsByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Widget::where('type', $type)
                ->with('layout')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get widgets by type: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get widget statistics
     */
    public function getWidgetStats(): array
    {
        try {
            $totalWidgets = Widget::count();
            $widgetsByType = Widget::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray();

            $layoutsWithWidgets = ScreenLayout::has('widgets')->count();
            $totalLayouts = ScreenLayout::count();

            return [
                'total_widgets' => $totalWidgets,
                'widgets_by_type' => $widgetsByType,
                'layouts_with_widgets' => $layoutsWithWidgets,
                'total_layouts' => $totalLayouts,
                'average_widgets_per_layout' => $totalLayouts > 0 ? 
                    round($totalWidgets / $totalLayouts, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get widget stats: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get real-time widget data for a device
     */
    public function getRealTimeData(string $deviceId, bool $includeQueueData = true, bool $includeWeatherData = true): array
    {
        try {
            $layout = ScreenLayout::where('device_id', $deviceId)
                ->where('is_default', true)
                ->with('widgets')
                ->first();

            if (!$layout) {
                return [
                    'device_id' => $deviceId,
                    'timestamp' => now()->toISOString(),
                    'widgets' => []
                ];
            }

            $realTimeData = [];
            foreach ($layout->widgets as $widget) {
                $widgetRealTimeData = $this->generateRealTimeWidgetData($widget, $includeQueueData, $includeWeatherData);
                
                $realTimeData[] = [
                    'id' => $widget->id,
                    'type' => $widget->type,
                    'data' => $widgetRealTimeData
                ];
            }

            return [
                'device_id' => $deviceId,
                'timestamp' => now()->toISOString(),
                'widgets' => $realTimeData
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get real-time widget data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update widget settings
     */
    public function updateWidgetSettings(Widget $widget, array $settings): Widget
    {
        try {
            $widget->update(['settings' => $settings]);
            return $widget->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update widget settings: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get widget preview data
     */
    public function getWidgetPreviewData(string $widgetType, array $settings = []): array
    {
        try {
            return $this->generateWidgetPreviewData($widgetType, $settings);
        } catch (\Exception $e) {
            Log::error('Failed to get widget preview data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate widget data based on type
     */
    private function generateWidgetData(Widget $widget): array
    {
        switch ($widget->type) {
            case 'time':
                return [
                    'current_time' => now()->format('H:i:s'),
                    'timezone' => config('app.timezone'),
                    'format' => $widget->settings['format'] ?? 'H:i:s'
                ];
            
            case 'date':
                return [
                    'current_date' => now()->format('l, F j, Y'),
                    'day_of_week' => now()->format('l'),
                    'format' => $widget->settings['format'] ?? 'l, F j, Y'
                ];
            
            case 'weather':
                return $this->getWeatherData($widget->settings);
            
            case 'queue':
                return $this->getQueueData($widget->settings);
            
            case 'announcement':
                return [
                    'message' => $widget->settings['message'] ?? 'Welcome to our service!',
                    'type' => $widget->settings['type'] ?? 'info',
                    'duration' => $widget->settings['duration'] ?? 5000
                ];
            
            case 'custom':
                return $widget->settings['data'] ?? [];
            
            default:
                return [];
        }
    }

    /**
     * Generate real-time widget data
     */
    private function generateRealTimeWidgetData(Widget $widget, bool $includeQueueData, bool $includeWeatherData): array
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
                return $includeWeatherData ? $this->getWeatherData($widget->settings) : [];
            
            case 'queue':
                return $includeQueueData ? $this->getQueueData($widget->settings) : [];
            
            case 'announcement':
                return [
                    'message' => $widget->settings['message'] ?? 'Welcome to our service!',
                    'type' => $widget->settings['type'] ?? 'info'
                ];
            
            default:
                return [];
        }
    }

    /**
     * Generate widget preview data
     */
    private function generateWidgetPreviewData(string $widgetType, array $settings = []): array
    {
        switch ($widgetType) {
            case 'time':
                return [
                    'current_time' => now()->format('H:i:s'),
                    'timezone' => config('app.timezone'),
                    'format' => $settings['format'] ?? 'H:i:s'
                ];
            
            case 'date':
                return [
                    'current_date' => now()->format('l, F j, Y'),
                    'day_of_week' => now()->format('l'),
                    'format' => $settings['format'] ?? 'l, F j, Y'
                ];
            
            case 'weather':
                return [
                    'temperature' => '22°C',
                    'condition' => 'Sunny',
                    'location' => $settings['location'] ?? 'New York',
                    'humidity' => '65%',
                    'wind_speed' => '5 km/h'
                ];
            
            case 'queue':
                return [
                    'current_number' => 'A001',
                    'estimated_wait' => '5 minutes',
                    'total_in_queue' => 12,
                    'queue_name' => $settings['queue_name'] ?? 'Main Queue'
                ];
            
            case 'announcement':
                return [
                    'message' => $settings['message'] ?? 'Welcome to our service!',
                    'type' => $settings['type'] ?? 'info',
                    'duration' => $settings['duration'] ?? 5000
                ];
            
            case 'custom':
                return $settings['data'] ?? [];
            
            default:
                return [];
        }
    }

    /**
     * Get weather data (mock implementation)
     */
    private function getWeatherData(array $settings = []): array
    {
        // In a real implementation, this would call a weather API
        return [
            'temperature' => '22°C',
            'condition' => 'Sunny',
            'location' => $settings['location'] ?? 'New York',
            'humidity' => '65%',
            'wind_speed' => '5 km/h',
            'updated_at' => now()->toISOString()
        ];
    }

    /**
     * Get queue data
     */
    private function getQueueData(array $settings = []): array
    {
        try {
            $queueId = $settings['queue_id'] ?? null;
            
            if (!$queueId) {
                // Get the first active queue
                $queue = Queue::where('status', 'active')->first();
            } else {
                $queue = Queue::find($queueId);
            }

            if (!$queue) {
                return [
                    'current_number' => 'N/A',
                    'estimated_wait' => 'N/A',
                    'total_in_queue' => 0,
                    'queue_name' => 'No active queue'
                ];
            }

            $activeEntries = $queue->entries()
                ->whereIn('order_status', ['queued', 'kitchen', 'preparing'])
                ->count();

            return [
                'current_number' => $queue->current_number > 0 ? 'A' . str_pad($queue->current_number, 3, '0', STR_PAD_LEFT) : 'N/A',
                'estimated_wait' => $this->calculateEstimatedWait($activeEntries),
                'total_in_queue' => $activeEntries,
                'queue_name' => $queue->name,
                'queue_status' => $queue->status
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get queue data: ' . $e->getMessage());
            return [
                'current_number' => 'Error',
                'estimated_wait' => 'Error',
                'total_in_queue' => 0,
                'queue_name' => 'Error loading queue data'
            ];
        }
    }

    /**
     * Calculate estimated wait time
     */
    private function calculateEstimatedWait(int $queueLength): string
    {
        // Simple calculation: 2 minutes per person
        $estimatedMinutes = $queueLength * 2;
        
        if ($estimatedMinutes < 1) {
            return 'Ready';
        } elseif ($estimatedMinutes < 60) {
            return $estimatedMinutes . ' minutes';
        } else {
            $hours = floor($estimatedMinutes / 60);
            $minutes = $estimatedMinutes % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }
}
