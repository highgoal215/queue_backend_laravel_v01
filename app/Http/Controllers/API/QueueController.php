<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class QueueController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Display a listing of queues
     */
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

    /**
     * Store a newly created queue
     */
    public function store(StoreQueueRequest $request): JsonResponse
    {
        try {
            $queue = $this->queueService->createQueue($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $queue,
                'message' => 'Queue created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create queue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified queue
     */
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

    /**
     * Update the specified queue
     */
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

    /**
     * Remove the specified queue
     */
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

    /**
     * Reset queue to 0
     */
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

    /**
     * Pause queue
     */
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

    /**
     * Resume queue
     */
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

    /**
     * Close queue
     */
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

    /**
     * Get queue status
     */
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

    /**
     * Call next number
     */
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

    /**
     * Skip current number
     */
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

    /**
     * Recall current number
     */
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

    /**
     * Adjust inventory stock (for inventory queues)
     */
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

    /**
     * Undo last entry (for inventory queues)
     */
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

    /**
     * Get queue entries
     */
    public function entries(Queue $queue): JsonResponse
    {
        try {
            $entries = $queue->entries()
                ->with(['cashier', 'tracking'])
                ->orderBy('queue_number', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $entries,
                'message' => 'Queue entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue analytics
     */
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
}
