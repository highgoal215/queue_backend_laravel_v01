<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQueueEntryRequest;
use App\Http\Requests\UpdateQueueEntryStatusRequest;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Services\QueueEntryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class QueueEntryController extends Controller
{
    protected $queueEntryService;

    public function __construct(QueueEntryService $queueEntryService)
    {
        $this->queueEntryService = $queueEntryService;
    }

    /**
     * Display a listing of queue entries
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'cashier_id', 'date', 'queue_id']);
            
            if (isset($filters['queue_id'])) {
                $queue = Queue::findOrFail($filters['queue_id']);
                $entries = $this->queueEntryService->getEntriesByQueue($queue, $filters);
            } else {
                $entries = QueueEntry::with(['queue', 'cashier', 'tracking'])
                    ->when(isset($filters['status']), function ($query) use ($filters) {
                        return $query->where('order_status', $filters['status']);
                    })
                    ->when(isset($filters['cashier_id']), function ($query) use ($filters) {
                        return $query->where('cashier_id', $filters['cashier_id']);
                    })
                    ->when(isset($filters['date']), function ($query) use ($filters) {
                        return $query->whereDate('created_at', $filters['date']);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            
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
     * Store a newly created queue entry
     */
    public function store(StoreQueueEntryRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            if (isset($data['order_details']) && is_string($data['order_details'])) {
                $decoded = json_decode($data['order_details'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['order_details'] = $decoded;
                }
            }
            
            $entry = $this->queueEntryService->createEntry($data);
            
            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Queue entry created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create queue entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified queue entry
     */
    public function show(QueueEntry $entry): JsonResponse
    {
        try {
            $entry->load(['queue', 'cashier', 'tracking']);
            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Queue entry details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue entry details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified queue entry
     */
    public function update(Request $request, QueueEntry $entry): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_name' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:20',
                'order_details' => 'nullable|json',
                'quantity_purchased' => 'nullable|integer|min:1',
                'estimated_wait_time' => 'nullable|integer|min:1',
                'notes' => 'nullable|string',
                'cashier_id' => 'nullable|exists:cashiers,id',
                'order_status' => 'sometimes|in:queued,kitchen,preparing,serving,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entry->update($validator->validated());
            $entry->refresh();
            $entry->load(['queue', 'cashier', 'tracking']);
            return response()->json([
                'success' => true,
                'data' => $entry,
                'message' => 'Queue entry updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update queue entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified queue entry
     */
    public function destroy(QueueEntry $entry): JsonResponse
    {
        try {
            $entry->delete();
            
            // Reset auto-increment ID to 0
            DB::statement('ALTER TABLE queue_entries AUTO_INCREMENT = 0');
            
            return response()->json([
                'success' => true,
                'message' => 'Queue entry deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete queue entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update queue entry status
     */
    public function updateStatus(UpdateQueueEntryStatusRequest $request, QueueEntry $entry): JsonResponse
    {
        try {
            $updatedEntry = $this->queueEntryService->updateStatus($entry, $request->validated());
            
            return response()->json([
                'success' => true,
                'data' => $updatedEntry,
                'message' => 'Entry status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update queue entry status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel queue entry
     */
    public function cancel(Request $request, QueueEntry $entry): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $cancelledEntry = $this->queueEntryService->cancelEntry($entry, $reason);
            
            return response()->json([
                'success' => true,
                'data' => $cancelledEntry,
                'message' => 'Queue entry cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel queue entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entries by status
     */
    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $queueId = $request->input('queue_id');
            $queue = $queueId ? Queue::find($queueId) : null;
            
            $entries = $this->queueEntryService->getEntriesByStatus($status, $queue);
            
            return response()->json([
                'success' => true,
                'data' => $entries,
                'message' => "Queue entries with status '{$status}' retrieved successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active entries for a queue
     */
    public function getActiveEntries(Queue $queue): JsonResponse
    {
        try {
            $entries = $this->queueEntryService->getActiveEntries($queue);
            
            return response()->json([
                'success' => true,
                'data' => $entries,
                'message' => 'Active queue entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active queue entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get next entry to be served
     */
    public function getNextEntry(Queue $queue): JsonResponse
    {
        try {
            $nextEntry = $this->queueEntryService->getNextEntry($queue);
            
            return response()->json([
                'success' => true,
                'data' => $nextEntry,
                'message' => $nextEntry ? 'Next entry retrieved successfully' : 'No entries in queue'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve next entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entries by cashier
     */
    public function getByCashier(Request $request, int $cashierId): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'date']);
            $entries = $this->queueEntryService->getEntriesByCashier($cashierId, $filters);
            
            return response()->json([
                'success' => true,
                'data' => $entries,
                'message' => 'Cashier entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cashier entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entry statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $queueId = $request->input('queue_id');
            
            $query = QueueEntry::query();
            if ($queueId) {
                $query->where('queue_id', $queueId);
            }
            
            $totalEntries = $query->count();
            $completedCount = (clone $query)->where('order_status', 'completed')->count();
            $cancelledCount = (clone $query)->where('order_status', 'cancelled')->count();
            $queuedCount = (clone $query)->where('order_status', 'queued')->count();
            $preparingCount = (clone $query)->where('order_status', 'preparing')->count();
            $readyCount = (clone $query)->where('order_status', 'serving')->count();
            
            $completionRate = $totalEntries > 0 ? round(($completedCount / $totalEntries) * 100, 2) : 0;
            $averageWaitTime = (clone $query)->whereNotNull('estimated_wait_time')->avg('estimated_wait_time') ?? 0;
            
            $stats = [
                'total_entries' => $totalEntries,
                'completed_count' => $completedCount,
                'cancelled_count' => $cancelledCount,
                'queued_count' => $queuedCount,
                'preparing_count' => $preparingCount,
                'ready_count' => $readyCount,
                'completion_rate' => $completionRate,
                'average_wait_time' => $averageWaitTime,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Entry statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve entry statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update entry statuses
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entry_ids' => 'required|array',
                'entry_ids.*' => 'exists:queue_entries,id',
                'order_status' => 'required|in:queued,kitchen,preparing,serving,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entryIds = $request->input('entry_ids');
            $newStatus = $request->input('order_status');
            $updatedEntries = [];

            foreach ($entryIds as $entryId) {
                $entry = QueueEntry::find($entryId);
                if ($entry) {
                    $entry->update(['order_status' => $newStatus]);
                    $updatedEntries[] = $entry->fresh();
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $updatedEntries,
                'message' => 'Entries updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get entry timeline/history
     */
    public function getTimeline(QueueEntry $entry): JsonResponse
    {
        try {
            $timeline = [
                [
                    'action' => 'entry_created',
                    'timestamp' => $entry->created_at,
                    'details' => 'Queue entry created'
                ],
                [
                    'action' => 'status_updated',
                    'timestamp' => $entry->updated_at,
                    'details' => "Status changed to {$entry->order_status}"
                ]
            ];

            // Add more timeline events if needed
            if ($entry->cashier) {
                $timeline[] = [
                    'action' => 'cashier_assigned',
                    'timestamp' => $entry->updated_at,
                    'details' => "Assigned to cashier {$entry->cashier->name}"
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'entry_id' => $entry->id,
                    'timeline' => $timeline
                ],
                'message' => 'Entry timeline retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve entry timeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search entries
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', $request->input('query'));
            $filters = $request->only(['status', 'queue_id', 'cashier_id', 'date']);

            $entries = QueueEntry::with(['queue', 'cashier', 'tracking'])
                ->when($query, function ($q) use ($query) {
                    return $q->where('customer_name', 'like', "%{$query}%");
                })
                ->when(isset($filters['status']), function ($q) use ($filters) {
                    return $q->where('order_status', $filters['status']);
                })
                ->when(isset($filters['queue_id']), function ($q) use ($filters) {
                    return $q->where('queue_id', $filters['queue_id']);
                })
                ->when(isset($filters['cashier_id']), function ($q) use ($filters) {
                    return $q->where('cashier_id', $filters['cashier_id']);
                })
                ->when(isset($filters['date']), function ($q) use ($filters) {
                    return $q->whereDate('created_at', $filters['date']);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $entries,
                'message' => 'Search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all entries with specific details for display
     */
    public function getAllEntriesWithDetails(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'cashier_id', 'date', 'queue_id', 'search']);
            
            $query = QueueEntry::with(['queue', 'cashier'])
                ->select([
                    'id',
                    'queue_id',
                    'customer_name',
                    'order_details',
                    'order_status',
                    'estimated_wait_time',
                    'cashier_id',
                    'created_at',
                    'updated_at'
                ]);

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('order_status', $filters['status']);
            }

            if (isset($filters['cashier_id'])) {
                $query->where('cashier_id', $filters['cashier_id']);
            }

            if (isset($filters['queue_id'])) {
                $query->where('queue_id', $filters['queue_id']);
            }

            if (isset($filters['date'])) {
                $query->whereDate('created_at', $filters['date']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('customer_name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('queue_number', 'like', '%' . $filters['search'] . '%');
                });
            }

            $entries = $query->orderBy('created_at', 'desc')->get();

            // Transform data to match requested format
            $transformedEntries = $entries->map(function ($entry) {
                // Calculate wait time if not set
                $waitTime = $entry->estimated_wait_time;
                if (!$waitTime) {
                    $createdTime = $entry->created_at;
                    $now = now();
                    $waitTime = $createdTime->diffInMinutes($now);
                }

                // Determine available actions based on status
                $actions = $this->getAvailableActions($entry->order_status);

                return [
                    'id' => $entry->id,
                    'customer_name' => $entry->customer_name ?? 'Anonymous',
                    'order_details' => $entry->order_details ?? [],
                    'status' => $entry->order_status,
                    'wait_time' => $waitTime . ' minutes',
                    'cashier' => $entry->cashier ? [
                        'id' => $entry->cashier->id,
                        'name' => $entry->cashier->name,
                        'is_active' => $entry->cashier->is_active
                    ] : null,
                    'actions' => $actions,
                    'queue_info' => [
                        'id' => $entry->queue->id,
                        'name' => $entry->queue->name,
                        'queue_number' => $entry->queue_number ?? 'N/A'
                    ],
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $entry->updated_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $transformedEntries,
                    'total_count' => $transformedEntries->count(),
                    'filters_applied' => $filters
                ],
                'message' => 'Entries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available actions for a given status
     */
    private function getAvailableActions(string $status): array
    {
        $actions = [];

        switch ($status) {
            case 'queued':
                $actions = ['start_preparing', 'cancel', 'assign_cashier'];
                break;
            case 'preparing':
                $actions = ['mark_ready', 'cancel', 'extend_wait_time'];
                break;
            case 'ready':
                $actions = ['serve', 'recall', 'extend_wait_time'];
                break;
            case 'serving':
                $actions = ['complete', 'extend_wait_time'];
                break;
            case 'completed':
                $actions = ['view_details'];
                break;
            case 'cancelled':
                $actions = ['view_details', 'reactivate'];
                break;
            default:
                $actions = ['view_details'];
        }

        return $actions;
    }
}
