<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ScreenLayout;
use App\Models\Widget;
use App\Services\LayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ScreenLayoutController extends Controller
{
    protected $layoutService;

    public function __construct(LayoutService $layoutService)
    {
        $this->layoutService = $layoutService;
    }

    /**
     * Display a listing of screen layouts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['device_id', 'name', 'is_default']);
            $layouts = $this->layoutService->getAllLayouts($filters);
            
            return response()->json([
                'success' => true,
                'data' => $layouts,
                'message' => 'Screen layouts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve screen layouts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created screen layout
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $layout = $this->layoutService->createLayout($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $layout,
                'message' => 'Screen layout created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create screen layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified screen layout
     */
    public function show(ScreenLayout $layout): JsonResponse
    {
        try {
            $layout->load('widgets');
            
            return response()->json([
                'success' => true,
                'data' => $layout,
                'message' => 'Screen layout details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve screen layout details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified screen layout
     */
    public function update(Request $request, ScreenLayout $layout): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'layout_config' => 'sometimes|required|array',
                'layout_config.grid' => 'required_with:layout_config|array',
                'layout_config.grid.columns' => 'required_with:layout_config.grid|integer|min:1|max:12',
                'layout_config.grid.rows' => 'required_with:layout_config.grid|integer|min:1|max:12',
                'layout_config.widgets' => 'array',
                'layout_config.widgets.*.type' => 'required|string|in:time,date,weather,queue,announcement,custom',
                'layout_config.widgets.*.position' => 'required|array',
                'layout_config.widgets.*.position.x' => 'required|integer|min:0',
                'layout_config.widgets.*.position.y' => 'required|integer|min:0',
                'layout_config.widgets.*.position.width' => 'required|integer|min:1',
                'layout_config.widgets.*.position.height' => 'required|integer|min:1',
                'layout_config.widgets.*.settings' => 'array',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedLayout = $this->layoutService->updateLayout($layout, $request->all());
            
            return response()->json([
                'success' => true,
                'data' => $updatedLayout,
                'message' => 'Screen layout updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update screen layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified screen layout
     */
    public function destroy(ScreenLayout $layout): JsonResponse
    {
        try {
            $this->layoutService->deleteLayout($layout);
            
            return response()->json([
                'success' => true,
                'message' => 'Screen layout deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete screen layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get layout by device ID
     */
    public function getByDevice(string $deviceId): JsonResponse
    {
        try {
            $layout = $this->layoutService->getLayoutByDevice($deviceId);
            
            if (!$layout) {
                return response()->json([
                    'success' => false,
                    'message' => 'No default layout found for this device'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $layout,
                'message' => 'Device layout retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve device layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a layout as default for a device
     */
    public function setDefault(ScreenLayout $layout): JsonResponse
    {
        try {
            $updatedLayout = $this->layoutService->setAsDefault($layout);
            
            return response()->json([
                'success' => true,
                'data' => $updatedLayout,
                'message' => 'Layout set as default successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set layout as default: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a layout
     */
    public function duplicate(ScreenLayout $layout): JsonResponse
    {
        try {
            $newLayout = $this->layoutService->duplicateLayout($layout);
            
            return response()->json([
                'success' => true,
                'data' => $newLayout,
                'message' => 'Layout duplicated successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get layout preview data
     */
    public function preview(ScreenLayout $layout): JsonResponse
    {
        try {
            $previewData = $this->layoutService->getPreviewData($layout);
            
            return response()->json([
                'success' => true,
                'data' => $previewData,
                'message' => 'Layout preview data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve layout preview: ' . $e->getMessage()
            ], 500);
        }
    }
}
