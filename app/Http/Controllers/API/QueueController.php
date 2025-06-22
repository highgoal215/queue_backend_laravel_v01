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

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Queue Management API",
 *     description="API for managing queues and queue entries",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class QueueController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * @OA\Get(
     *     path="/queues",
     *     summary="Get all queues",
     *     description="Retrieve a list of all queues",
     *     tags={"Queues"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Customer Service"),
     *                     @OA\Property(property="description", type="string", example="Main customer service queue"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="current_number", type="integer", example=5),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Post(
     *     path="/queues",
     *     summary="Create a new queue",
     *     description="Create a new queue with the provided data",
     *     tags={"Queues"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Customer Service", description="Queue name"),
     *             @OA\Property(property="description", type="string", example="Main customer service queue", description="Queue description"),
     *             @OA\Property(property="type", type="string", example="service", description="Queue type (service, inventory, etc.)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Queue created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Customer Service"),
     *                 @OA\Property(property="description", type="string", example="Main customer service queue"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="current_number", type="integer", example=0),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Get(
     *     path="/queues/{queue}",
     *     summary="Get a specific queue",
     *     description="Retrieve details of a specific queue by ID",
     *     tags={"Queues"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Customer Service"),
     *                 @OA\Property(property="description", type="string", example="Main customer service queue"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="current_number", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Put(
     *     path="/queues/{queue}",
     *     summary="Update a queue",
     *     description="Update an existing queue with new data",
     *     tags={"Queues"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Customer Service", description="Queue name"),
     *             @OA\Property(property="description", type="string", example="Updated description", description="Queue description"),
     *             @OA\Property(property="status", type="string", example="paused", description="Queue status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Customer Service"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="status", type="string", example="paused"),
     *                 @OA\Property(property="current_number", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Delete(
     *     path="/queues/{queue}",
     *     summary="Delete a queue",
     *     description="Delete a queue and all its associated entries",
     *     tags={"Queues"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Post(
     *     path="/queues/{queue}/reset",
     *     summary="Reset a queue",
     *     description="Reset the queue counter to 0 and clear all entries",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue reset successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue reset successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_number", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Post(
     *     path="/queues/{queue}/pause",
     *     summary="Pause a queue",
     *     description="Pause the queue operations",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue paused successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue paused successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="paused")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Post(
     *     path="/queues/{queue}/resume",
     *     summary="Resume a queue",
     *     description="Resume the queue operations",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue resumed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue resumed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Post(
     *     path="/queues/{queue}/call-next",
     *     summary="Call next number",
     *     description="Call the next number in the queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Next number called successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Next number called successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_number", type="integer", example=6),
     *                 @OA\Property(property="called_number", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No entries in queue"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Get(
     *     path="/queues/{queue}/status",
     *     summary="Get queue status",
     *     description="Get the current status and statistics of a queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue status retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Customer Service"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="current_number", type="integer", example=5),
     *                 @OA\Property(property="total_entries", type="integer", example=25),
     *                 @OA\Property(property="waiting_entries", type="integer", example=8),
     *                 @OA\Property(property="average_wait_time", type="integer", example=300),
     *                 @OA\Property(property="last_updated", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Get(
     *     path="/queues/{queue}/entries",
     *     summary="Get queue entries",
     *     description="Get all entries for a specific queue",
     *     tags={"Queue Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by entry status",
     *         @OA\Schema(type="string", example="waiting")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of entries per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue entries retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="queue_id", type="integer", example=1),
     *                     @OA\Property(property="number", type="integer", example=5),
     *                     @OA\Property(property="status", type="string", example="waiting"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
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
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve queue entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/queues/{queue}/close",
     *     summary="Close a queue",
     *     description="Close the queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue closed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Queue closed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="closed")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Post(
     *     path="/queues/{queue}/skip",
     *     summary="Skip current number",
     *     description="Skip the current number in the queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Current number skipped successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Current number skipped successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_number", type="integer", example=6)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function skip(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->skipCurrent($queue);
            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Current number skipped successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to skip number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/queues/{queue}/recall",
     *     summary="Recall current number",
     *     description="Recall the current number in the queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Current number recalled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Current number recalled successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_number", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function recall(Queue $queue): JsonResponse
    {
        try {
            $updatedQueue = $this->queueService->recallCurrent($queue);
            return response()->json([
                'success' => true,
                'data' => $updatedQueue,
                'message' => 'Current number recalled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to recall number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/queues/{queue}/adjust-stock",
     *     summary="Adjust inventory stock",
     *     description="Adjust the inventory stock for a specific queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="quantity", type="integer", example=10, description="Quantity to adjust"),
     *             @OA\Property(property="operation", type="string", example="add", description="Operation to perform (add, subtract)"),
     *             @OA\Property(property="item_id", type="integer", example=1, description="Item ID to adjust")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock adjusted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Stock adjusted successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_quantity", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function adjustStock(Request $request, Queue $queue): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:0',
                'operation' => 'required|in:add,subtract'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedQueue = $this->queueService->adjustStock($queue, $request->quantity, $request->operation);
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
     * @OA\Post(
     *     path="/queues/{queue}/undo-last-entry",
     *     summary="Undo last entry",
     *     description="Undo the last entry in the queue",
     *     tags={"Queue Operations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Last entry undone successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Last entry undone successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_number", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
     * @OA\Get(
     *     path="/queues/{queue}/analytics",
     *     summary="Get queue analytics",
     *     description="Get the analytics for a specific queue",
     *     tags={"Queue Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         required=true,
     *         description="Queue ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue analytics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Customer Service"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="current_number", type="integer", example=5),
     *                 @OA\Property(property="total_entries", type="integer", example=25),
     *                 @OA\Property(property="waiting_entries", type="integer", example=8),
     *                 @OA\Property(property="average_wait_time", type="integer", example=300),
     *                 @OA\Property(property="last_updated", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
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
