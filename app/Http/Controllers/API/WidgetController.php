<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Widget;
use App\Models\ScreenLayout;
use App\Services\WidgetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WidgetController extends Controller
{
    protected $widgetService;

    public function __construct(WidgetService $widgetService)
    {
        $this->widgetService = $widgetService;
    }

    /**
     * Fetch widget data for display
     */
    public function fetch(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_id' => 'required|string',
                'widget_types' => 'sometimes|array',
                'widget_types.*' => 'string|in:time,date,weather,queue,announcement,custom'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deviceId = $request->input('device_id');
            $widgetTypes = $request->input('widget_types', []);

            $widgetData = $this->widgetService->getWidgetData($deviceId, $widgetTypes);
            
            return response()->json([
                'success' => true,
                'data' => $widgetData,
                'message' => 'Widget data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch widget data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get widgets for a specific layout
     */
    public function getByLayout(ScreenLayout $layout): JsonResponse
    {
        try {
            $widgets = $this->widgetService->getWidgetsByLayout($layout);
            
            return response()->json([
                'success' => true,
                'data' => $widgets,
                'message' => 'Layout widgets retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve layout widgets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get widgets by type
     */
    public function getByType(Request $request, string $type): JsonResponse
    {
        try {
            $validator = Validator::make(['type' => $type], [
                'type' => 'required|string|in:time,date,weather,queue,announcement,custom'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid widget type',
                    'errors' => $validator->errors()
                ], 422);
            }

            $widgets = $this->widgetService->getWidgetsByType($type);
            
            return response()->json([
                'success' => true,
                'data' => $widgets,
                'message' => "Widgets of type '{$type}' retrieved successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve widgets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get widget statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->widgetService->getWidgetStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Widget statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve widget statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time widget data for a specific device
     */
    public function getRealTimeData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_id' => 'required|string',
                'include_queue_data' => 'boolean',
                'include_weather_data' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deviceId = $request->input('device_id');
            $includeQueueData = $request->boolean('include_queue_data', true);
            $includeWeatherData = $request->boolean('include_weather_data', true);

            $realTimeData = $this->widgetService->getRealTimeData($deviceId, $includeQueueData, $includeWeatherData);
            
            return response()->json([
                'success' => true,
                'data' => $realTimeData,
                'message' => 'Real-time widget data retrieved successfully',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve real-time data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update widget settings
     */
    public function updateSettings(Request $request, Widget $widget): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedWidget = $this->widgetService->updateWidgetSettings($widget, $request->input('settings'));
            
            return response()->json([
                'success' => true,
                'data' => $updatedWidget,
                'message' => 'Widget settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update widget settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get widget preview data
     */
    public function getPreviewData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'widget_type' => 'required|string|in:time,date,weather,queue,announcement,custom',
                'settings' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $widgetType = $request->input('widget_type');
            $settings = $request->input('settings', []);

            $previewData = $this->widgetService->getWidgetPreviewData($widgetType, $settings);
            
            return response()->json([
                'success' => true,
                'data' => $previewData,
                'message' => 'Widget preview data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve widget preview: ' . $e->getMessage()
            ], 500);
        }
    }
}
