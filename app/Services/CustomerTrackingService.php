<?php

namespace App\Services;

use App\Models\CustomerTracking;
use App\Models\QueueEntry;
use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerTrackingService
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Get all tracking records with optional filters
     */
    public function getAllTrackingRecords(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = CustomerTracking::with(['entry.queue', 'entry.cashier']);

        if (isset($filters['queue_id'])) {
            $query->whereHas('entry', function ($q) use ($filters) {
                $q->where('queue_id', $filters['queue_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->whereHas('entry', function ($q) use ($filters) {
                $q->where('order_status', $filters['status']);
            });
        }

        if (isset($filters['date'])) {
            $query->whereHas('entry', function ($q) use ($filters) {
                $q->whereDate('created_at', $filters['date']);
            });
        }

        if (isset($filters['has_qr_code'])) {
            if ($filters['has_qr_code']) {
                $query->whereNotNull('qr_code_url');
            } else {
                $query->whereNull('qr_code_url');
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get tracking info for a specific entry
     */
    public function getTrackingInfo($entry_id): array
    {
        $tracking = CustomerTracking::where('queue_entry_id', $entry_id)
            ->with(['entry.queue', 'entry.cashier'])
            ->firstOrFail();

        $entry = $tracking->entry;

        $data = [
            'tracking_id' => $tracking->id,
            'queue_number' => $entry->queue_number,
            'order_status' => $entry->order_status,
            'queue_name' => $entry->queue->name,
            'queue_type' => $entry->queue->type,
            'cashier' => $entry->cashier ? [
                'name' => $entry->cashier->name,
                'is_active' => $entry->cashier->is_active,
            ] : null,
            'created_at' => $entry->created_at,
            'updated_at' => $entry->updated_at,
            'qr_code_url' => $tracking->qr_code_url,
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($entry),
        ];

        // Add inventory info for inventory queues
        if ($entry->queue->type === 'inventory') {
            $data['inventory_info'] = [
                'quantity_purchased' => $entry->quantity_purchased,
                'remaining_quantity' => $entry->queue->remaining_quantity,
            ];
        }

        return $data;
    }

    /**
     * Create a new tracking record
     */
    public function createTrackingRecord(array $data): CustomerTracking
    {
        try {
            // Check if tracking record already exists
            $existingTracking = CustomerTracking::where('queue_entry_id', $data['queue_entry_id'])->first();
            
            if ($existingTracking) {
                throw new \Exception('Tracking record already exists for this entry');
            }

            // Generate QR code if not provided
            if (!isset($data['qr_code_url'])) {
                $entry = QueueEntry::findOrFail($data['queue_entry_id']);
                $qrCodeData = [
                    'entry_id' => $entry->id,
                    'queue_number' => $entry->queue_number,
                    'queue_name' => $entry->queue->name,
                    'timestamp' => $entry->created_at->toISOString(),
                ];
                $data['qr_code_url'] = $this->qrCodeService->generateQRCode($qrCodeData);
            }

            $tracking = CustomerTracking::create($data);
            $tracking->load(['entry.queue', 'entry.cashier']);

            return $tracking;
        } catch (\Exception $e) {
            Log::error('Failed to create tracking record: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing tracking record
     */
    public function updateTrackingRecord(CustomerTracking $tracking, array $data): CustomerTracking
    {
        try {
            $tracking->update($data);
            $tracking->load(['entry.queue', 'entry.cashier']);

            return $tracking;
        } catch (\Exception $e) {
            Log::error('Failed to update tracking record: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a tracking record
     */
    public function deleteTrackingRecord(CustomerTracking $tracking): bool
    {
        try {
            $tracking->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete tracking record: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update order status for a queue entry
     */
    public function updateOrderStatus($entry_id, array $data): QueueEntry
    {
        try {
            $entry = QueueEntry::findOrFail($entry_id);
            
            $oldStatus = $entry->order_status;
            $newStatus = $data['order_status'];

            // Validate status transition
            $this->validateStatusTransition($oldStatus, $newStatus);

            $entry->update([
                'order_status' => $newStatus,
            ]);

            // Broadcast status change event
            event(new OrderStatusChanged($entry));

            return $entry->load(['queue', 'cashier', 'tracking']);
        } catch (\Exception $e) {
            Log::error('Failed to update order status: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate QR code for tracking
     */
    public function generateQRCode($entry_id): array
    {
        try {
            $entry = QueueEntry::findOrFail($entry_id);
            
            $qrCodeData = [
                'entry_id' => $entry->id,
                'queue_number' => $entry->queue_number,
                'queue_name' => $entry->queue->name,
                'timestamp' => $entry->created_at->toISOString(),
            ];

            $qrCodeUrl = $this->qrCodeService->generateQRCode($qrCodeData);

            // Create or update tracking record
            $tracking = CustomerTracking::updateOrCreate(
                ['queue_entry_id' => $entry_id],
                ['qr_code_url' => $qrCodeUrl]
            );

            return [
                'tracking_id' => $tracking->id,
                'qr_code_url' => $qrCodeUrl,
                'entry_id' => $entry_id,
                'queue_number' => $entry->queue_number,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get tracking statistics
     */
    public function getTrackingStats(array $filters = []): array
    {
        try {
            $query = CustomerTracking::query();

            if (isset($filters['queue_id'])) {
                $query->whereHas('entry', function ($q) use ($filters) {
                    $q->where('queue_id', $filters['queue_id']);
                });
            }

            if (isset($filters['date_range'])) {
                $dates = explode(',', $filters['date_range']);
                if (count($dates) === 2) {
                    $query->whereHas('entry', function ($q) use ($dates) {
                        $q->whereBetween('created_at', $dates);
                    });
                }
            }

            $totalTracking = $query->count();
            $trackingWithQR = $query->whereNotNull('qr_code_url')->count();

            $statusDistribution = $query->get()->groupBy(function ($tracking) {
                return $tracking->entry->order_status;
            })->map->count();

            $dailyTracking = $query->get()->groupBy(function ($tracking) {
                return $tracking->created_at->format('Y-m-d');
            })->map->count();

            return [
                'total_tracking_records' => $totalTracking,
                'tracking_with_qr_codes' => $trackingWithQR,
                'tracking_without_qr_codes' => $totalTracking - $trackingWithQR,
                'status_distribution' => $statusDistribution,
                'daily_tracking' => $dailyTracking,
                'average_tracking_per_day' => $dailyTracking->count() > 0 ? 
                    round($totalTracking / $dailyTracking->count(), 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get tracking stats: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get tracking history for a queue entry
     */
    public function getTrackingHistory($entry_id): array
    {
        try {
            $entry = QueueEntry::findOrFail($entry_id);
            $tracking = CustomerTracking::where('queue_entry_id', $entry_id)->first();

            $history = [
                'entry_id' => $entry->id,
                'queue_number' => $entry->queue_number,
                'queue_name' => $entry->queue->name,
                'current_status' => $entry->order_status,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
                'cashier' => $entry->cashier ? $entry->cashier->name : null,
                'tracking_info' => $tracking ? [
                    'tracking_id' => $tracking->id,
                    'qr_code_url' => $tracking->qr_code_url,
                    'created_at' => $tracking->created_at,
                ] : null,
                'status_timeline' => [
                    [
                        'status' => 'queued',
                        'timestamp' => $entry->created_at,
                        'description' => 'Order placed in queue'
                    ],
                    // Add more status changes as they occur
                ]
            ];

            // Add estimated completion time if available
            if ($entry->order_status !== 'completed' && $entry->order_status !== 'cancelled') {
                $history['estimated_completion'] = $this->calculateEstimatedCompletion($entry);
            }

            return $history;
        } catch (\Exception $e) {
            Log::error('Failed to get tracking history: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search tracking records
     */
    public function searchTrackingRecords(string $query, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        try {
            $searchQuery = CustomerTracking::with(['entry.queue', 'entry.cashier']);

            // Apply search query
            $searchQuery->where(function ($q) use ($query) {
                $q->whereHas('entry', function ($entryQuery) use ($query) {
                    $entryQuery->where('queue_number', 'like', "%{$query}%")
                        ->orWhereHas('queue', function ($queueQuery) use ($query) {
                            $queueQuery->where('name', 'like', "%{$query}%");
                        })
                        ->orWhereHas('cashier', function ($cashierQuery) use ($query) {
                            $cashierQuery->where('name', 'like', "%{$query}%");
                        });
                });
            });

            // Apply filters
            if (isset($filters['status'])) {
                $searchQuery->whereHas('entry', function ($q) use ($filters) {
                    $q->where('order_status', $filters['status']);
                });
            }

            if (isset($filters['queue_id'])) {
                $searchQuery->whereHas('entry', function ($q) use ($filters) {
                    $q->where('queue_id', $filters['queue_id']);
                });
            }

            if (isset($filters['date'])) {
                $searchQuery->whereHas('entry', function ($q) use ($filters) {
                    $q->whereDate('created_at', $filters['date']);
                });
            }

            return $searchQuery->orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            Log::error('Failed to search tracking records: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get real-time tracking updates
     */
    public function getRealTimeUpdates(array $entry_ids): array
    {
        try {
            $updates = [];

            foreach ($entry_ids as $entry_id) {
                $entry = QueueEntry::with(['queue', 'cashier', 'tracking'])->find($entry_id);
                
                if ($entry) {
                    $updates[] = [
                        'entry_id' => $entry->id,
                        'queue_number' => $entry->queue_number,
                        'order_status' => $entry->order_status,
                        'queue_name' => $entry->queue->name,
                        'cashier' => $entry->cashier ? $entry->cashier->name : null,
                        'updated_at' => $entry->updated_at,
                        'tracking_url' => $entry->tracking ? $entry->tracking->qr_code_url : null,
                        'estimated_wait_time' => $this->calculateEstimatedWaitTime($entry),
                    ];
                }
            }

            return $updates;
        } catch (\Exception $e) {
            Log::error('Failed to get real-time updates: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Bulk update tracking records
     */
    public function bulkUpdateTrackingRecords(array $data): array
    {
        try {
            $trackingIds = $data['tracking_ids'];
            $updateData = array_filter($data, function ($key) {
                return $key !== 'tracking_ids';
            }, ARRAY_FILTER_USE_KEY);

            $updatedRecords = [];

            foreach ($trackingIds as $trackingId) {
                $tracking = CustomerTracking::find($trackingId);
                if ($tracking) {
                    $tracking->update($updateData);
                    $tracking->load(['entry.queue', 'entry.cashier']);
                    $updatedRecords[] = $tracking;
                }
            }

            return $updatedRecords;
        } catch (\Exception $e) {
            Log::error('Failed to bulk update tracking records: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate estimated wait time for an entry
     */
    private function calculateEstimatedWaitTime(QueueEntry $entry): string
    {
        try {
            $queue = $entry->queue;
            $positionInQueue = $queue->entries()
                ->where('order_status', 'queued')
                ->where('queue_number', '<=', $entry->queue_number)
                ->count();

            // Simple calculation: 2 minutes per person ahead
            $estimatedMinutes = $positionInQueue * 2;

            if ($estimatedMinutes < 1) {
                return 'Ready';
            } elseif ($estimatedMinutes < 60) {
                return $estimatedMinutes . ' minutes';
            } else {
                $hours = floor($estimatedMinutes / 60);
                $minutes = $estimatedMinutes % 60;
                return $hours . 'h ' . $minutes . 'm';
            }
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Calculate estimated completion time
     */
    private function calculateEstimatedCompletion(QueueEntry $entry): ?string
    {
        try {
            $waitTime = $this->calculateEstimatedWaitTime($entry);
            
            if ($waitTime === 'Ready') {
                return now()->addMinutes(5)->format('H:i');
            }

            // Parse wait time and add to current time
            if (preg_match('/(\d+) minutes/', $waitTime, $matches)) {
                $minutes = (int) $matches[1];
                return now()->addMinutes($minutes)->format('H:i');
            }

            if (preg_match('/(\d+)h (\d+)m/', $waitTime, $matches)) {
                $hours = (int) $matches[1];
                $minutes = (int) $matches[2];
                return now()->addHours($hours)->addMinutes($minutes)->format('H:i');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate status transition
     */
    private function validateStatusTransition(string $oldStatus, string $newStatus): void
    {
        $validTransitions = [
            'queued' => ['kitchen', 'cancelled'],
            'kitchen' => ['preparing', 'cancelled'],
            'preparing' => ['serving', 'cancelled'],
            'serving' => ['completed', 'cancelled'],
            'completed' => [], // No further transitions
            'cancelled' => [], // No further transitions
        ];

        if (!isset($validTransitions[$oldStatus]) || !in_array($newStatus, $validTransitions[$oldStatus])) {
            throw new \Exception("Invalid status transition from '{$oldStatus}' to '{$newStatus}'");
        }
    }
} 