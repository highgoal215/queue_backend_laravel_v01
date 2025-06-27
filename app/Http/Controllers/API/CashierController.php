<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashierRequest;
use App\Models\Cashier;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            
            // Reset auto-increment ID to 0
            DB::statement('ALTER TABLE cashiers AUTO_INCREMENT = 0');
            
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

    /**
     * Get detailed cashier information with performance metrics and current status
     */
    public function getDetailedInfo(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'assigned_queue_id', 'role', 'status', 'is_available']);
            
            $query = Cashier::with(['queue', 'queue.entries' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(7)) // Last 7 days
                      ->orderBy('created_at', 'desc');
            }]);

            // Apply filters
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }
            if (isset($filters['assigned_queue_id'])) {
                $query->where('assigned_queue_id', $filters['assigned_queue_id']);
            }
            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['is_available'])) {
                $query->where('is_available', $filters['is_available']);
            }

            $cashiers = $query->orderBy('name')->get();

            // Transform data to include detailed information
            $detailedCashiers = $cashiers->map(function ($cashier) {
                // Calculate performance metrics
                $recentEntries = $cashier->queue ? $cashier->queue->entries->where('cashier_id', $cashier->id) : collect();
                $todayEntries = $recentEntries->where('created_at', '>=', now()->startOfDay());
                $weekEntries = $recentEntries->where('created_at', '>=', now()->subDays(7));
                
                // Calculate average service time (in minutes)
                $avgServiceTime = $cashier->average_service_time ?: 0;
                
                // Determine current workload
                $currentWorkload = $recentEntries->whereIn('order_status', ['queued', 'preparing', 'ready'])->count();
                
                // Calculate efficiency score (entries per hour)
                $efficiencyScore = 0;
                if ($cashier->total_served > 0 && $avgServiceTime > 0) {
                    $efficiencyScore = round(60 / $avgServiceTime, 2); // entries per hour
                }

                // Get shift status
                $shiftStatus = $this->getShiftStatus($cashier);
                
                // Get current customer info
                $currentCustomer = null;
                if ($cashier->current_customer_id) {
                    $currentCustomer = \App\Models\QueueEntry::find($cashier->current_customer_id);
                }

                return [
                    'id' => $cashier->id,
                    'basic_info' => [
                        'name' => $cashier->name,
                        'employee_id' => $cashier->employee_id,
                        'email' => $cashier->email,
                        'phone' => $cashier->phone,
                        'role' => $cashier->role,
                    ],
                    'status_info' => [
                        'is_active' => $cashier->is_active,
                        'is_available' => $cashier->is_available,
                        'status' => $cashier->status,
                        'shift_status' => $shiftStatus,
                        'current_workload' => $currentWorkload,
                    ],
                    'shift_info' => [
                        'shift_start' => $cashier->shift_start ? $cashier->shift_start->format('H:i') : null,
                        'shift_end' => $cashier->shift_end ? $cashier->shift_end->format('H:i') : null,
                        'is_on_shift' => $this->isOnShift($cashier),
                    ],
                    'queue_assignment' => $cashier->queue ? [
                        'queue_id' => $cashier->queue->id,
                        'queue_name' => $cashier->queue->name,
                        'queue_type' => $cashier->queue->type,
                        'queue_status' => $cashier->queue->status,
                        'current_number' => $cashier->queue->current_number,
                    ] : null,
                    'performance_metrics' => [
                        'total_served' => $cashier->total_served,
                        'average_service_time' => $avgServiceTime . ' minutes',
                        'efficiency_score' => $efficiencyScore . ' entries/hour',
                        'today_entries' => $todayEntries->count(),
                        'week_entries' => $weekEntries->count(),
                        'completion_rate' => $this->calculateCompletionRate($recentEntries),
                    ],
                    'current_customer' => $currentCustomer ? [
                        'customer_id' => $currentCustomer->id,
                        'customer_name' => $currentCustomer->customer_name,
                        'queue_number' => $currentCustomer->queue_number,
                        'order_status' => $currentCustomer->order_status,
                        'wait_time' => $currentCustomer->created_at->diffInMinutes(now()) . ' minutes',
                    ] : null,
                    'recent_activity' => [
                        'last_activity' => $recentEntries->first() ? $recentEntries->first()->created_at->format('Y-m-d H:i:s') : null,
                        'recent_entries_count' => $recentEntries->count(),
                        'status_distribution' => $this->getStatusDistribution($recentEntries),
                    ],
                    'actions' => $this->getAvailableActions($cashier),
                    'created_at' => $cashier->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $cashier->updated_at->format('Y-m-d H:i:s'),
                ];
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
     * Get shift status for a cashier
     */
    private function getShiftStatus(Cashier $cashier): string
    {
        if (!$cashier->shift_start || !$cashier->shift_end) {
            return 'no_shift_scheduled';
        }

        $now = now();
        $shiftStart = $cashier->shift_start;
        $shiftEnd = $cashier->shift_end;

        // Convert to today's date for comparison
        $todayShiftStart = now()->setTime($shiftStart->hour, $shiftStart->minute);
        $todayShiftEnd = now()->setTime($shiftEnd->hour, $shiftEnd->minute);

        if ($now->between($todayShiftStart, $todayShiftEnd)) {
            return 'on_shift';
        } elseif ($now < $todayShiftStart) {
            return 'before_shift';
        } else {
            return 'after_shift';
        }
    }

    /**
     * Check if cashier is currently on shift
     */
    private function isOnShift(Cashier $cashier): bool
    {
        return $this->getShiftStatus($cashier) === 'on_shift';
    }

    /**
     * Calculate completion rate for recent entries
     */
    private function calculateCompletionRate($entries): string
    {
        if ($entries->count() === 0) {
            return '0%';
        }

        $completedCount = $entries->where('order_status', 'completed')->count();
        $rate = round(($completedCount / $entries->count()) * 100, 1);
        return $rate . '%';
    }

    /**
     * Get status distribution for recent entries
     */
    private function getStatusDistribution($entries): array
    {
        $distribution = [];
        $statuses = ['queued', 'preparing', 'ready', 'serving', 'completed', 'cancelled'];
        
        foreach ($statuses as $status) {
            $count = $entries->where('order_status', $status)->count();
            if ($count > 0) {
                $distribution[$status] = $count;
            }
        }
        
        return $distribution;
    }

    /**
     * Get available actions for a cashier
     */
    private function getAvailableActions(Cashier $cashier): array
    {
        $actions = ['view_details', 'update_status'];

        if ($cashier->is_active) {
            $actions[] = 'set_inactive';
            if ($cashier->is_available) {
                $actions[] = 'set_unavailable';
                $actions[] = 'start_break';
            } else {
                $actions[] = 'set_available';
                if ($cashier->status === 'break') {
                    $actions[] = 'end_break';
                }
            }
        } else {
            $actions[] = 'set_active';
        }

        if ($cashier->assigned_queue_id) {
            $actions[] = 'unassign_queue';
        } else {
            $actions[] = 'assign_queue';
        }

        return $actions;
    }

    /**
     * Get essential cashier information with specific fields
     */
    public function getEssentialInfo(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_active', 'assigned_queue_id', 'role', 'status']);
            
            $query = Cashier::with('queue');

            // Apply filters
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }
            if (isset($filters['assigned_queue_id'])) {
                $query->where('assigned_queue_id', $filters['assigned_queue_id']);
            }
            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $cashiers = $query->orderBy('name')->get();

            // Transform data to include only essential information
            $essentialCashiers = $cashiers->map(function ($cashier) {
                return [
                    'id' => $cashier->id,
                    'cashier_name' => $cashier->name,
                    'employee_id' => $cashier->employee_id,
                    'role' => $cashier->role,
                    'status' => $cashier->status,
                    'shift_start' => $cashier->shift_start ? $cashier->shift_start->format('H:i') : null,
                    'shift_end' => $cashier->shift_end ? $cashier->shift_end->format('H:i') : null,
                    'queue_name' => $cashier->queue ? $cashier->queue->name : null,
                    'total_served' => $cashier->total_served,
                ];
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
