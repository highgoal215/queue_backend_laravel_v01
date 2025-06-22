<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashierRequest;
use App\Models\Cashier;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CashierController extends Controller
{
    /**
     * Display a listing of cashiers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'assigned_queue_id', 'role']);
            $query = Cashier::query();

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }
            if (isset($filters['assigned_queue_id'])) {
                $query->where('assigned_queue_id', $filters['assigned_queue_id']);
            }
            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            $cashiers = $query->with('queue')->orderBy('name')->get();

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
            $cashier = Cashier::create($request->validated());
            return response()->json([
                'success' => true,
                'data' => $cashier->fresh(),
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

            $cashier->update($validator->validated());
            $cashier->refresh();
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
            $cashier->delete();
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
            $cashier->assigned_queue_id = $request->assigned_queue_id;
            $cashier->save();
            $cashier->refresh();
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
            $cashier->is_active = $request->is_active;
            $cashier->is_available = $request->is_active;
            $cashier->status = $request->is_active ? 'active' : 'inactive';
            $cashier->save();
            return response()->json([
                'success' => true,
                'data' => $cashier,
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
            $queues = Queue::with('cashiers')->get();
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
}
