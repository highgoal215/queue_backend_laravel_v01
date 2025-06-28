<?php

namespace App\Services;

use App\Models\Cashier;
use App\Models\Queue;
use App\Models\QueueEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class CashierService
{
    /**
     * Create a new cashier
     */
    public function createCashier(array $data): Cashier
    {
        DB::beginTransaction();
        try {
            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_available'] = $data['is_available'] ?? true;
            $data['status'] = $data['status'] ?? 'active';
            $data['total_served'] = $data['total_served'] ?? 0;
            $data['average_service_time'] = $data['average_service_time'] ?? 0;

            // Convert time strings to proper format if provided
            if (isset($data['shift_start']) && is_string($data['shift_start'])) {
                $data['shift_start'] = \Carbon\Carbon::createFromFormat('H:i', $data['shift_start']);
            }
            if (isset($data['shift_end']) && is_string($data['shift_end'])) {
                $data['shift_end'] = \Carbon\Carbon::createFromFormat('H:i', $data['shift_end']);
            }

            $cashier = Cashier::create($data);
            
            Log::info('Cashier created', [
                'cashier_id' => $cashier->id,
                'name' => $cashier->name
            ]);

            DB::commit();
            return $cashier->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create cashier', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update cashier information
     */
    public function updateCashier(Cashier $cashier, array $data): Cashier
    {
        DB::beginTransaction();
        try {
            // Convert time strings to proper format if provided
            if (isset($data['shift_start']) && is_string($data['shift_start'])) {
                $data['shift_start'] = \Carbon\Carbon::createFromFormat('H:i', $data['shift_start']);
            }
            if (isset($data['shift_end']) && is_string($data['shift_end'])) {
                $data['shift_end'] = \Carbon\Carbon::createFromFormat('H:i', $data['shift_end']);
            }

            $cashier->update($data);
            
            Log::info('Cashier updated', [
                'cashier_id' => $cashier->id,
                'name' => $cashier->name
            ]);

            DB::commit();
            return $cashier->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update cashier', [
                'cashier_id' => $cashier->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete cashier
     */
    public function deleteCashier(Cashier $cashier): bool
    {
        DB::beginTransaction();
        try {
            // Check if cashier has active entries
            $activeEntries = QueueEntry::where('cashier_id', $cashier->id)
                ->whereIn('order_status', ['queued', 'preparing', 'ready', 'serving'])
                ->count();

            if ($activeEntries > 0) {
                throw new \Exception('Cannot delete cashier with active entries');
            }

            $cashier->delete();
            
            // Reset auto-increment ID to 1 if no cashiers exist
            $remainingCashiers = Cashier::count();
            if ($remainingCashiers === 0) {
                DB::statement('ALTER TABLE cashiers AUTO_INCREMENT = 1');
            }
            
            Log::info('Cashier deleted', [
                'cashier_id' => $cashier->id,
                'name' => $cashier->name
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete cashier', [
                'cashier_id' => $cashier->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assign cashier to a queue
     */
    public function assignToQueue(Cashier $cashier, int $queueId): Cashier
    {
        DB::beginTransaction();
        try {
            // Verify queue exists
            $queue = Queue::findOrFail($queueId);
            
            // Check if cashier is already assigned to this queue
            if ($cashier->assigned_queue_id === $queueId) {
                throw new \Exception('Cashier is already assigned to this queue');
            }

            $cashier->update(['assigned_queue_id' => $queueId]);
            
            Log::info('Cashier assigned to queue', [
                'cashier_id' => $cashier->id,
                'queue_id' => $queueId
            ]);

            DB::commit();
            return $cashier->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign cashier to queue', [
                'cashier_id' => $cashier->id,
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Set cashier active/inactive status
     */
    public function setActiveStatus(Cashier $cashier, bool $isActive): Cashier
    {
        DB::beginTransaction();
        try {
            $cashier->update([
                'is_active' => $isActive,
                'is_available' => $isActive,
                'status' => $isActive ? 'active' : 'inactive'
            ]);
            
            Log::info('Cashier status updated', [
                'cashier_id' => $cashier->id,
                'is_active' => $isActive
            ]);

            DB::commit();
            return $cashier->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update cashier status', [
                'cashier_id' => $cashier->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get cashiers with filters
     */
    public function getCashiers(array $filters = []): Collection
    {
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
        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get cashier with detailed information
     */
    public function getCashierWithDetails(Cashier $cashier): array
    {
        $cashier->load(['queue', 'queue.entries' => function ($query) {
            $query->where('created_at', '>=', now()->subDays(7))
                  ->orderBy('created_at', 'desc');
        }]);

        // Calculate performance metrics
        $recentEntries = $cashier->queue ? $cashier->queue->entries->where('cashier_id', $cashier->id) : collect();
        $todayEntries = $recentEntries->where('created_at', '>=', now()->startOfDay());
        $weekEntries = $recentEntries->where('created_at', '>=', now()->subDays(7));
        
        // Calculate average service time
        $avgServiceTime = $cashier->average_service_time ?: 0;
        
        // Determine current workload
        $currentWorkload = $recentEntries->whereIn('order_status', ['queued', 'preparing', 'ready'])->count();
        
        // Calculate efficiency score
        $efficiencyScore = 0;
        if ($cashier->total_served > 0 && $avgServiceTime > 0) {
            $efficiencyScore = round(60 / $avgServiceTime, 2);
        }

        // Get shift status
        $shiftStatus = $this->getShiftStatus($cashier);
        
        // Get current customer info
        $currentCustomer = null;
        if ($cashier->current_customer_id) {
            $currentCustomer = QueueEntry::find($cashier->current_customer_id);
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
    }

    /**
     * Get essential cashier information
     */
    public function getEssentialInfo(Cashier $cashier): array
    {
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
    }

    /**
     * Get all queues with their assigned cashiers
     */
    public function getQueuesWithCashiers(): Collection
    {
        return Queue::with('cashiers')->get();
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
} 