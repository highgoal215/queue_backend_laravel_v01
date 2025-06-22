<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerTracking;
use App\Models\QueueEntry;
use App\Services\CustomerTrackingService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CustomerTrackingController extends Controller
{
    protected $trackingService;
    protected $qrCodeService;

    public function __construct(CustomerTrackingService $trackingService, QRCodeService $qrCodeService)
    {
        $this->trackingService = $trackingService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * @OA\Get(
     *     path="/api/queues",
     *     summary="Get all queues",
     *     @OA\Response(response="200", description="List of queues")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['queue_id', 'status', 'date', 'has_qr_code']);
            $trackingRecords = $this->trackingService->getAllTrackingRecords($filters);
            
            return response()->json([
                'success' => true,
                'data' => $trackingRecords,
                'message' => 'Tracking records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show tracking info for a queue entry (QR code endpoint)
     */
    public function show($entry_id): JsonResponse
    {
        try {
            $trackingData = $this->trackingService->getTrackingInfo($entry_id);
            
            return response()->json([
                'success' => true,
                'data' => $trackingData,
                'message' => 'Tracking info retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking info: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a newly created tracking record
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'queue_entry_id' => 'required|exists:queue_entries,id',
                'qr_code_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $trackingRecord = $this->trackingService->createTrackingRecord($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $trackingRecord,
                'message' => 'Tracking record created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tracking record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified tracking record
     */
    public function update(Request $request, CustomerTracking $tracking): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'qr_code_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedTracking = $this->trackingService->updateTrackingRecord($tracking, $request->all());
            
            return response()->json([
                'success' => true,
                'data' => $updatedTracking,
                'message' => 'Tracking record updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tracking record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified tracking record
     */
    public function destroy(CustomerTracking $tracking): JsonResponse
    {
        try {
            $this->trackingService->deleteTrackingRecord($tracking);
            
            return response()->json([
                'success' => true,
                'message' => 'Tracking record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tracking record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status for a queue entry (for staff or API)
     */
    public function updateStatus(Request $request, $entry_id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_status' => 'required|in:queued,kitchen,preparing,serving,completed,cancelled',
                'notes' => 'nullable|string|max:500',
                'estimated_completion_time' => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedEntry = $this->trackingService->updateOrderStatus($entry_id, $request->all());
            
            return response()->json([
                'success' => true,
                'data' => $updatedEntry,
                'message' => 'Order status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR code for tracking
     */
    public function generateQRCode(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entry_id' => 'required|exists:queue_entries,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $qrCodeData = $this->trackingService->generateQRCode($request->entry_id);
            
            return response()->json([
                'success' => true,
                'data' => $qrCodeData,
                'message' => 'QR code generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tracking statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['queue_id', 'date_range']);
            $stats = $this->trackingService->getTrackingStats($filters);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Tracking statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tracking history for a queue entry
     */
    public function getHistory($entry_id): JsonResponse
    {
        try {
            $history = $this->trackingService->getTrackingHistory($entry_id);
            
            return response()->json([
                'success' => true,
                'data' => $history,
                'message' => 'Tracking history retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search tracking records
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');
            $filters = $request->only(['status', 'queue_id', 'date']);

            $results = $this->trackingService->searchTrackingRecords($query, $filters);
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search tracking records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time tracking updates
     */
    public function getRealTimeUpdates(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entry_ids' => 'required|array',
                'entry_ids.*' => 'exists:queue_entries,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updates = $this->trackingService->getRealTimeUpdates($request->entry_ids);
            
            return response()->json([
                'success' => true,
                'data' => $updates,
                'message' => 'Real-time updates retrieved successfully',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve real-time updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update tracking records
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tracking_ids' => 'required|array',
                'tracking_ids.*' => 'exists:customer_tracking,id',
                'qr_code_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedRecords = $this->trackingService->bulkUpdateTrackingRecords($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $updatedRecords,
                'message' => count($updatedRecords) . ' tracking records updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update tracking records: ' . $e->getMessage()
            ], 500);
        }
    }
}
