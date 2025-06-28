<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashierRequest;
use App\Http\Requests\UpdateCashierRequest;
use App\Models\Cashier;
use App\Services\CashierService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CashierController extends Controller
{
    protected CashierService $cashierService;

    public function __construct(CashierService $cashierService)
    {
        $this->cashierService = $cashierService;
    }

    /**
     * Display a listing of cashiers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'assigned_queue_id', 'role', 'status', 'is_available']);
            $cashiers = $this->cashierService->getCashiers($filters);

            return response()->json([
                'success' => true,
                'data' => $cashiers,
                'message' => 'Cashiers retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cashiers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created cashier
     */
    public function store(StoreCashierRequest $request): JsonResponse
    {
        try {
            $cashier = $this->cashierService->createCashier($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $cashier->load('queue'),
                'message' => 'Cashier created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cashier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified cashier
     */
    public function show(Cashier $cashier): JsonResponse
    {
        try {
            $cashier->load('queue');
            $data = $cashier->toArray();
            $data['queue'] = $cashier->queue ? [
                'id' => $cashier->queue->id,
                'name' => $cashier->queue->name,
                'type' => $cashier->queue->type
            ] : null;
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Cashier details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cashier details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified cashier
     */
    public function update(Request $request, Cashier $cashier): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:cashiers,name,' . $cashier->id,
                'assigned_queue_id' => 'nullable|exists:queues,id',
                'is_active' => 'sometimes|boolean',
                'email' => 'nullable|email|unique:cashiers,email,' . $cashier->id,
                'phone' => 'nullable|string|max:20',
                'role' => 'nullable|string|max:100',
                'shift_start' => 'nullable|date_format:H:i',
                'shift_end' => 'nullable|date_format:H:i|after:shift_start',
                'status' => 'sometimes|in:active,inactive,break',
                'is_available' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedCashier = $this->cashierService->updateCashier($cashier, $validator->validated());
            $updatedCashier->load('queue');
            
            $data = $updatedCashier->toArray();
            $data['queue'] = $updatedCashier->queue ? [
                'id' => $updatedCashier->queue->id,
                'name' => $updatedCashier->queue->name,
                'type' => $updatedCashier->queue->type
            ] : null;
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Cashier updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cashier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified cashier
     */
    public function destroy(Cashier $cashier): JsonResponse
    {
        try {
            $this->cashierService->deleteCashier($cashier);
            
            return response()->json([
                'success' => true,
                'message' => 'Cashier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cashier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign cashier to a queue
     */
    public function assignToQueue(Request $request, Cashier $cashier): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'assigned_queue_id' => 'required|exists:queues,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $updatedCashier = $this->cashierService->assignToQueue($cashier, $request->assigned_queue_id);
            $updatedCashier->load('queue');
            
            $data = $updatedCashier->toArray();
            $data['queue'] = $updatedCashier->queue ? [
                'id' => $updatedCashier->queue->id,
                'name' => $updatedCashier->queue->name,
                'type' => $updatedCashier->queue->type
            ] : null;
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Cashier assigned to queue successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign cashier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate or deactivate a cashier
     */
    public function setActive(Request $request, Cashier $cashier): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $updatedCashier = $this->cashierService->setActiveStatus($cashier, $request->is_active);
            
            return response()->json([
                'success' => true,
                'data' => $updatedCashier,
                'message' => 'Cashier status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cashier status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all queues with their assigned cashiers
     */
    public function queuesWithCashiers(): JsonResponse
    {
        try {
            $queues = $this->cashierService->getQueuesWithCashiers();
            
            return response()->json([
                'success' => true,
                'data' => $queues,
                'message' => 'Queues with cashiers retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queues: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed cashier information with performance metrics and current status
     */
    public function getDetailedInfo(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'assigned_queue_id', 'role', 'status', 'is_available']);
            $cashiers = $this->cashierService->getCashiers($filters);

            // Transform data to include detailed information
            $detailedCashiers = $cashiers->map(function ($cashier) {
                return $this->cashierService->getCashierWithDetails($cashier);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'cashiers' => $detailedCashiers,
                    'total_count' => $detailedCashiers->count(),
                    'filters_applied' => $filters,
                    'summary' => [
                        'active_cashiers' => $detailedCashiers->where('status_info.is_active', true)->count(),
                        'available_cashiers' => $detailedCashiers->where('status_info.is_available', true)->count(),
                        'on_shift_cashiers' => $detailedCashiers->where('shift_info.is_on_shift', true)->count(),
                    ]
                ],
                'message' => 'Detailed cashier information retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve detailed cashier information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get essential cashier information with specific fields
     */
    public function getEssentialInfo(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'assigned_queue_id', 'role', 'status']);
            $cashiers = $this->cashierService->getCashiers($filters);

            // Transform data to include only essential information
            $essentialCashiers = $cashiers->map(function ($cashier) {
                return $this->cashierService->getEssentialInfo($cashier);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'cashiers' => $essentialCashiers,
                    'total_count' => $essentialCashiers->count(),
                    'filters_applied' => $filters
                ],
                'message' => 'Essential cashier information retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve essential cashier information: ' . $e->getMessage()
            ], 500);
        }
    }
}
