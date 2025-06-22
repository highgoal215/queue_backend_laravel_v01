<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\QueueEntry;
use App\Events\QueueUpdated;
use App\Events\StockDepleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueService
{
    /**
     * Create a new queue
     */
    public function createQueue(array $data): Queue
    {
        $queue = Queue::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'max_quantity' => $data['type'] === 'inventory' ? $data['max_quantity'] : null,
            'remaining_quantity' => $data['type'] === 'inventory' ? $data['remaining_quantity'] : null,
            'status' => $data['status'] ?? 'active',
            'current_number' => 0,
        ]);

        // Broadcast queue creation event
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Get next queue number
     */
    public function getNextNumber(Queue $queue): int
    {
        if ($queue->status !== 'active') {
            throw new \Exception('Queue is not active');
        }

        $nextNumber = $queue->current_number + 1;
        
        $queue->update(['current_number' => $nextNumber]);
        
        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $nextNumber;
    }

    /**
     * Reset queue to 0
     */
    public function resetQueue(Queue $queue): Queue
    {
        $queue->update(['current_number' => 0]);
        
        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Pause queue
     */
    public function pauseQueue(Queue $queue): Queue
    {
        $queue->update(['status' => 'paused']);
        
        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Resume queue
     */
    public function resumeQueue(Queue $queue): Queue
    {
        $queue->update(['status' => 'active']);
        
        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Close queue
     */
    public function closeQueue(Queue $queue): Queue
    {
        $queue->update(['status' => 'closed']);
        
        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Add entry to queue
     */
    public function addEntry(Queue $queue, array $data): QueueEntry
    {
        if ($queue->status !== 'active') {
            throw new \Exception('Queue is not active');
        }

        // For inventory queues, check stock availability
        if ($queue->type === 'inventory') {
            $requestedQuantity = $data['quantity_purchased'] ?? 0;
            
            if ($requestedQuantity > $queue->remaining_quantity) {
                throw new \Exception('Requested quantity exceeds remaining stock');
            }

            if ($queue->remaining_quantity <= 0) {
                throw new \Exception('Stock is depleted');
            }
        }

        DB::beginTransaction();
        try {
            // Get next number for this entry
            $nextNumber = $this->getNextNumber($queue);
            
            // Create queue entry
            $entry = QueueEntry::create([
                'queue_id' => $queue->id,
                'queue_number' => $nextNumber,
                'quantity_purchased' => $data['quantity_purchased'] ?? null,
                'cashier_id' => $data['cashier_id'] ?? null,
                'order_status' => 'queued',
            ]);

            // Update remaining quantity for inventory queues
            if ($queue->type === 'inventory' && isset($data['quantity_purchased'])) {
                $newRemaining = $queue->remaining_quantity - $data['quantity_purchased'];
                $queue->update(['remaining_quantity' => $newRemaining]);

                // Check if stock is depleted
                if ($newRemaining <= 0) {
                    $queue->update(['status' => 'closed']);
                    event(new StockDepleted($queue));
                }
            }

            DB::commit();
            
            // Broadcast queue update
            event(new QueueUpdated($queue));

            return $entry;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update entry status
     */
    public function updateEntryStatus(QueueEntry $entry, string $status): QueueEntry
    {
        $validStatuses = ['queued', 'kitchen', 'preparing', 'serving', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status');
        }

        $entry->update(['order_status' => $status]);
        
        // Broadcast queue update
        event(new QueueUpdated($entry->queue));

        return $entry;
    }

    /**
     * Skip current number
     */
    public function skipNumber(Queue $queue): Queue
    {
        if ($queue->status !== 'active') {
            throw new \Exception('Queue is not active');
        }

        // Just increment the number without creating an entry
        $queue->update(['current_number' => $queue->current_number + 1]);
        
        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Recall current number
     */
    public function recallNumber(Queue $queue): Queue
    {
        if ($queue->status !== 'active') {
            throw new \Exception('Queue is not active');
        }

        // Broadcast recall event (same as queue update for now)
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Adjust inventory stock
     */
    public function adjustStock(Queue $queue, int $newQuantity): Queue
    {
        if ($queue->type !== 'inventory') {
            throw new \Exception('Can only adjust stock for inventory queues');
        }

        if ($newQuantity < 0) {
            throw new \Exception('Stock quantity cannot be negative');
        }

        $queue->update(['remaining_quantity' => $newQuantity]);

        // If stock is now available, reopen queue if it was closed
        if ($newQuantity > 0 && $queue->status === 'closed') {
            $queue->update(['status' => 'active']);
        }

        // Broadcast queue update
        event(new QueueUpdated($queue));

        return $queue;
    }

    /**
     * Undo last entry (for inventory queues)
     */
    public function undoLastEntry(Queue $queue): bool
    {
        if ($queue->type !== 'inventory') {
            throw new \Exception('Can only undo entries for inventory queues');
        }

        $lastEntry = $queue->entries()
            ->whereNotNull('quantity_purchased')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastEntry) {
            throw new \Exception('No entries to undo');
        }

        DB::beginTransaction();
        try {
            // Restore the quantity
            $queue->update([
                'remaining_quantity' => $queue->remaining_quantity + $lastEntry->quantity_purchased,
                'current_number' => max(0, $queue->current_number - 1)
            ]);

            // Delete the entry
            $lastEntry->delete();

            // If queue was closed due to stock depletion, reopen it
            if ($queue->status === 'closed' && $queue->remaining_quantity > 0) {
                $queue->update(['status' => 'active']);
            }

            DB::commit();
            
            // Broadcast queue update
            event(new QueueUpdated($queue));

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(Queue $queue): array
    {
        $totalEntries = $queue->entries()->count();
        $completedEntries = $queue->entries()->where('order_status', 'completed')->count();
        $pendingEntries = $queue->entries()->whereIn('order_status', ['queued', 'kitchen', 'preparing'])->count();

        $stats = [
            'total_entries' => $totalEntries,
            'completed_entries' => $completedEntries,
            'pending_entries' => $pendingEntries,
            'current_number' => $queue->current_number,
            'status' => $queue->status,
        ];

        if ($queue->type === 'inventory') {
            $stats['remaining_quantity'] = $queue->remaining_quantity;
            $stats['sold_quantity'] = $queue->max_quantity - $queue->remaining_quantity;
            $stats['total_quantity'] = $queue->max_quantity;
        }

        return $stats;
    }

    /**
     * Get queue entries with pagination
     */
    public function getQueueEntries(Queue $queue, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $queue->entries()
            ->with(['cashier', 'tracking'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('order_status', $filters['status']);
        }

        if (isset($filters['cashier_id'])) {
            $query->where('cashier_id', $filters['cashier_id']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Get all queues with their current status
     */
    public function getAllQueues(): \Illuminate\Database\Eloquent\Collection
    {
        return Queue::with(['entries' => function ($query) {
            $query->whereIn('order_status', ['queued', 'kitchen', 'preparing'])
                  ->orderBy('queue_number', 'asc');
        }])->get();
    }
}
