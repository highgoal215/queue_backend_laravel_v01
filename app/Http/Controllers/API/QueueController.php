<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }


    public function index(): JsonResponse
    {
        try {
            $queues = $this->queueService->getAllQueues();

            return response()->json([
                'success' => true,
                'data' => $queues,
                'message' => 'Queues retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queues: ' . $e->getMessage()
            ], 500);
        }
    }


    public function store(StoreQueueRequest $request): JsonResponse
    {
        // Debug logging to see if function is called
        Log::info('Queue store function called', [
            'request_data' => $request->all(),
            'validated_data' => $request->validated(),
            'user' => $request->user()
        ]);

        try {
            $queue = $this->queueService->createQueue($request->validated());

            Log::info(message: 'Queue-----------<>', context: [
                'data' => $queue
            ]);

            return response()->json([
                'success' => true,
                'data' => $queue,
                'message' => 'Queue created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Queue store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create queue: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show(Queue $queue): JsonResponse
    {
        try {
            $queue->load(['entries' => function ($query) {
                $query->orderBy('queue_number', 'asc');
            }, 'cashiers']);
            $stats = $this->queueService->getQueueStats($queue);
            $queueArr = $queue->toArray();
            $queueArr['entries'] = $queue->entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'customer_name' => $entry->customer_name,
                    'order_status' => $entry->order_status
                ];
            });
            $queueArr['cashiers'] = $queue->cashiers->map(function ($cashier) {
                return [
                    'id' => $cashier->id,
                    'name' => $cashier->name,
                    'status' => $cashier->status
                ];
            });
            return response()->json([
                'success' => true,
                'data' => [
                    'queue' => $queueArr,
                    'statistics' => $stats + [
                        'queued_count' => $stats['queued_count'] ?? 0,
                        'preparing_count' => $stats['preparing_count'] ?? 0,
                        'ready_count' => $stats['ready_count'] ?? 0,
                        'total_entries' => $stats['total_entries'] ?? 0,
                        'current_number' => $queue->current_number
                    ]
                ],
                'message' => 'Queue details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue details: ' . $e->getMessage()
            ], 500);
        }
    }


    public function update(UpdateQueueRequest $request, Queue $queue): JsonResponse
    {
        try {
            $queue->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => $queue,
                'message' => 'Queue updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Queue $queue): JsonResponse
    {
        try {
            // Check if queue has active entries
            $activeEntries = $queue->entries()->whereIn('order_status', ['queued', 'kitchen', 'preparing'])->count();

            if ($activeEntries > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete queue with active entries'
                ], 400);
            }

            $queue->delete();

            // Reset auto-increment ID to 0
            DB::statement('ALTER TABLE queues AUTO_INCREMENT = 0');

            return response()->json([
                'success' => true,
                'message' => 'Queue deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reset(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->resetQueue($queue);
            // For inventory queues, also reset remaining_quantity
            if ($updatedQueue->type === 'inventory') {
                $updatedQueue->update(['remaining_quantity' => $updatedQueue->max_quantity]);
            }
            return response()->json([
                'success' => true,
                'data' => $updatedQueue->fresh(),
                'message' => 'Queue reset successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pause(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->pauseQueue($queue);

            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Queue paused successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resume(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->resumeQueue($queue);

            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Queue resumed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function callNext(Queue $queue): JsonResponse
    {
        try {
            $nextNumber = $this->queueService->getNextNumber($queue);

            // Get the entry that was just called
            $calledEntry = $queue->entries()
                ->where('queue_number', $nextNumber)
                ->where('order_status', 'serving')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'next_number' => $nextNumber,
                    'called_entry' => $calledEntry,
                    'queue' => $queue->fresh()
                ],
                'message' => 'Next number called successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to call next number: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status(Queue $queue): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'current_number' => $queue->current_number,
                    'status' => $queue->status,
                    'total_entries' => $queue->entries()->count(),
                    'queued_count' => $queue->entries()->where('order_status', 'queued')->count(),
                    'preparing_count' => $queue->entries()->where('order_status', 'preparing')->count(),
                    'ready_count' => $queue->entries()->where('order_status', 'serving')->count()
                ],
                'message' => 'Queue status retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function entries(Request $request, Queue $queue): JsonResponse
    {
        try {
            $entries = $this->queueService->getQueueEntries($queue, $request->all());

            return response()->json([
                'success' => true,
                'data' => $entries->items(),
                'pagination' => [
                    'current_page' => $entries->currentPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total()
                ],
                'message' => 'Queue entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue entries: ' . $e->getMessage()
            ], 500);
        }
    }

    public function close(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->closeQueue($queue);

            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Queue closed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close queue: ' . $e->getMessage()
            ], 500);
        }
    }

    public function skip(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->skipNumber($queue);

            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Number skipped successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to skip number: ' . $e->getMessage()
            ], 500);
        }
    }

    public function recall(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->recallNumber($queue);

            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Number recalled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to recall number: ' . $e->getMessage()
            ], 500);
        }
    }

    public function adjustStock(Request $request, Queue $queue): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_quantity' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedQueue = $this->queueService->adjustStock($queue, $request->new_quantity);

            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Stock adjusted successfully'
            ]);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Can only adjust stock for inventory queues') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock adjustment only available for inventory queues'
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function undoLastEntry(Queue $queue): JsonResponse
    {
        try {
            $result = $this->queueService->undoLastEntry($queue);

            return response()->json([
                'success' => true,
                'data' => $queue->fresh(),
                'message' => 'Last entry undone successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to undo last entry: ' . $e->getMessage()
            ], 500);
        }
    }

    public function analytics(Queue $queue): JsonResponse
    {
        try {
            $stats = $this->queueService->getQueueStats($queue);
            $stats['completed_count'] = $stats['completed_entries'] ?? 0;
            $stats['cancelled_count'] = $queue->entries()->where('order_status', 'cancelled')->count();

            // Calculate completion rate
            $totalEntries = $stats['total_entries'] ?? 0;
            $completedCount = $stats['completed_count'] ?? 0;
            $stats['completion_rate'] = $totalEntries > 0 ? round(($completedCount / $totalEntries) * 100, 2) : 0;

            // Add average wait time
            $stats['average_wait_time'] = $queue->entries()->whereNotNull('estimated_wait_time')->avg('estimated_wait_time') ?? 0;

            // Add peak hours (placeholder)
            $stats['peak_hours'] = [];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Queue analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overall statistics for all queues
     */
    public function getStats(): JsonResponse
    {
        try {
            $queues = Queue::all();
            $totalQueues = $queues->count();
            $activeQueues = $queues->where('status', 'active')->count();
            $pausedQueues = $queues->where('status', 'paused')->count();
            $closedQueues = $queues->where('status', 'closed')->count();

            $totalEntries = 0;
            $completedEntries = 0;
            $pendingEntries = 0;
            $cancelledEntries = 0;

            foreach ($queues as $queue) {
                $totalEntries += $queue->entries()->count();
                $completedEntries += $queue->entries()->where('order_status', 'completed')->count();
                $pendingEntries += $queue->entries()->whereIn('order_status', ['queued', 'kitchen', 'preparing'])->count();
                $cancelledEntries += $queue->entries()->where('order_status', 'cancelled')->count();
            }

            $stats = [
                'total_queues' => $totalQueues,
                'active_queues' => $activeQueues,
                'paused_queues' => $pausedQueues,
                'closed_queues' => $closedQueues,
                'total_entries' => $totalEntries,
                'completed_entries' => $completedEntries,
                'pending_entries' => $pendingEntries,
                'cancelled_entries' => $cancelledEntries,
                'completion_rate' => $totalEntries > 0 ? round(($completedEntries / $totalEntries) * 100, 2) : 0,
                'average_wait_time' => \App\Models\QueueEntry::whereNotNull('estimated_wait_time')->avg('estimated_wait_time') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Queue statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active entries for a specific queue
     */
    public function getActiveEntries(Queue $queue): JsonResponse
    {
        try {
            $activeEntries = $queue->entries()
                ->whereIn('order_status', ['queued', 'kitchen', 'preparing', 'serving'])
                ->with(['cashier', 'tracking'])
                ->orderBy('queue_number', 'asc')
                ->get();

            $transformedEntries = $activeEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'customer_name' => $entry->customer_name ?? 'Anonymous',
                    'order_details' => $entry->order_details,
                    'order_status' => $entry->order_status,
                    'estimated_wait_time' => $entry->estimated_wait_time,
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                    'cashier' => $entry->cashier ? [
                        'id' => $entry->cashier->id,
                        'name' => $entry->cashier->name
                    ] : null,
                    'tracking' => $entry->tracking ? [
                        'status' => $entry->tracking->status,
                        'last_updated' => $entry->tracking->updated_at->format('Y-m-d H:i:s')
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_name' => $queue->name,
                    'active_entries' => $transformedEntries,
                    'count' => $activeEntries->count()
                ],
                'message' => 'Active entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get completed entries for a specific queue
     */
    public function getCompletedEntries(Queue $queue): JsonResponse
    {
        try {
            $request = request();
            $perPage = $request->input('per_page', 15);
            $date = $request->input('date');

            $query = $queue->entries()
                ->where('order_status', 'completed')
                ->with(['cashier', 'tracking']);

            if ($date) {
                $query->whereDate('created_at', $date);
            }

            $completedEntries = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $transformedEntries = $completedEntries->getCollection()->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'customer_name' => $entry->customer_name ?? 'Anonymous',
                    'order_details' => $entry->order_details,
                    'quantity_purchased' => $entry->quantity_purchased,
                    'estimated_wait_time' => $entry->estimated_wait_time,
                    'completed_at' => $entry->updated_at->format('Y-m-d H:i:s'),
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                    'cashier' => $entry->cashier ? [
                        'id' => $entry->cashier->id,
                        'name' => $entry->cashier->name
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_name' => $queue->name,
                    'completed_entries' => $transformedEntries,
                    'pagination' => [
                        'current_page' => $completedEntries->currentPage(),
                        'last_page' => $completedEntries->lastPage(),
                        'per_page' => $completedEntries->perPage(),
                        'total' => $completedEntries->total(),
                        'from' => $completedEntries->firstItem(),
                        'to' => $completedEntries->lastItem(),
                    ]
                ],
                'message' => 'Completed entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve completed entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wait times for a specific queue
     */
    public function getWaitTimes(Queue $queue): JsonResponse
    {
        try {
            $request = request();
            $period = $request->input('period', 'today'); // today, week, month

            $query = $queue->entries()->whereNotNull('estimated_wait_time');

            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                default:
                    $query->whereDate('created_at', today());
            }

            $entries = $query->get();

            $averageWaitTime = $entries->avg('estimated_wait_time') ?? 0;
            $minWaitTime = $entries->min('estimated_wait_time') ?? 0;
            $maxWaitTime = $entries->max('estimated_wait_time') ?? 0;

            // Group by hour for trend analysis
            $hourlyData = $entries->groupBy(function ($entry) {
                return $entry->created_at->format('H');
            })->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'average_wait_time' => $group->avg('estimated_wait_time')
                ];
            });

            $waitTimeStats = [
                'queue_id' => $queue->id,
                'queue_name' => $queue->name,
                'period' => $period,
                'total_entries' => $entries->count(),
                'average_wait_time' => round($averageWaitTime, 2),
                'min_wait_time' => $minWaitTime,
                'max_wait_time' => $maxWaitTime,
                'hourly_trends' => $hourlyData,
                'wait_time_distribution' => [
                    'under_5_min' => $entries->where('estimated_wait_time', '<', 5)->count(),
                    '5_to_10_min' => $entries->whereBetween('estimated_wait_time', [5, 10])->count(),
                    '10_to_15_min' => $entries->whereBetween('estimated_wait_time', [10, 15])->count(),
                    '15_to_20_min' => $entries->whereBetween('estimated_wait_time', [15, 20])->count(),
                    'over_20_min' => $entries->where('estimated_wait_time', '>', 20)->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $waitTimeStats,
                'message' => 'Wait time statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wait time statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update queue entries
     */
    public function bulkUpdate(Request $request, Queue $queue): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entry_ids' => 'required|array',
                'entry_ids.*' => 'exists:queue_entries,id',
                'updates' => 'required|array',
                'updates.order_status' => 'sometimes|in:queued,kitchen,preparing,serving,completed,cancelled',
                'updates.cashier_id' => 'sometimes|exists:cashiers,id',
                'updates.estimated_wait_time' => 'sometimes|integer|min:1',
                'updates.notes' => 'sometimes|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entryIds = $request->input('entry_ids');
            $updates = $request->input('updates');
            $updatedEntries = [];

            // Verify all entries belong to the specified queue
            $entries = $queue->entries()->whereIn('id', $entryIds)->get();
            if ($entries->count() !== count($entryIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some entries do not belong to this queue'
                ], 400);
            }

            foreach ($entries as $entry) {
                $entry->update($updates);
                $updatedEntries[] = $entry->fresh()->load(['cashier', 'tracking']);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'updated_entries' => $updatedEntries,
                    'count' => count($updatedEntries)
                ],
                'message' => 'Entries updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update entries: ' . $e->getMessage()
            ], 500);
        }
    }
}
