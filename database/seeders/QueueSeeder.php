<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Queue;
use App\Models\Cashier;
use App\Models\QueueEntry;
use App\Models\CustomerTracking;

class QueueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample queues
        $regularQueue = Queue::create([
            'name' => 'Customer Service',
            'type' => 'regular',
            'max_quantity' => 1000,
            'status' => 'active',
            'current_number' => 5,
        ]);

        $inventoryQueue = Queue::create([
            'name' => 'Steak Promotion',
            'type' => 'inventory',
            'max_quantity' => 1000,
            'status' => 'active',
            'current_number' => 3,
        ]);

        // Create sample cashiers
        $cashier1 = Cashier::create([
            'name' => 'Cashier A',
            'assigned_queue_id' => $regularQueue->id,
            'is_active' => true,
        ]);

        $cashier2 = Cashier::create([
            'name' => 'Cashier B',
            'assigned_queue_id' => $inventoryQueue->id,
            'is_active' => true,
        ]);

        $cashier3 = Cashier::create([
            'name' => 'Cashier C',
            'assigned_queue_id' => null,
            'is_active' => true,
        ]);

        // Create sample queue entries for regular queue
        for ($i = 1; $i <= 5; $i++) {
            $entry = QueueEntry::create([
                'queue_id' => $regularQueue->id,
                'queue_number' => $i,
                'customer_name' => 'Customer ' . $i,
                'phone_number' => '555-000' . $i,
                'quantity_purchased' => 20 * $i,
                'cashier_id' => $cashier1->id,
                'order_status' => $i <= 3 ? 'completed' : ($i == 4 ? 'preparing' : 'queued'),
            ]);

            // Create tracking for completed entries
            if ($i <= 3) {
                CustomerTracking::create([
                    'queue_entry_id' => $entry->id,
                    'qr_code_url' => "https://example.com/qr/entry-{$entry->id}",
                ]);
            }
        }

        // Create sample queue entries for inventory queue
        for ($i = 1; $i <= 3; $i++) {
            $entry = QueueEntry::create([
                'queue_id' => $inventoryQueue->id,
                'queue_number' => $i,
                'quantity_purchased' => rand(50, 200),
                'cashier_id' => $cashier2->id,
                'order_status' => $i <= 2 ? 'completed' : 'queued',
            ]);

            // Create tracking for completed entries
            if ($i <= 2) {
                CustomerTracking::create([
                    'queue_entry_id' => $entry->id,
                    'qr_code_url' => "https://example.com/qr/entry-{$entry->id}",
                ]);
            }
        }

        // Create a paused queue
        Queue::create([
            'name' => 'Technical Support',
            'type' => 'regular',
            'status' => 'paused',
            'current_number' => 0,
        ]);

        // Create a closed inventory queue
        Queue::create([
            'name' => 'Limited Edition Sale',
            'type' => 'inventory',
            'max_quantity' => 500,
            'remaining_quantity' => 0,
            'status' => 'closed',
            'current_number' => 25,
        ]);
    }
} 