<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QueueService;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Cashier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class QueueServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueService = new QueueService();
    }

    public function test_it_can_get_all_queues()
    {
        // Arrange
        Queue::factory()->count(3)->create();

        // Act
        $queues = $this->queueService->getAllQueues();

        // Assert
        $this->assertCount(3, $queues);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $queues);
    }

    public function test_it_can_create_a_queue()
    {
        // Arrange
        $queueData = [
            'name' => 'Test Queue',
            'type' => 'regular',
            'max_quantity' => 100,
            'status' => 'active'
        ];

        // Act
        $queue = $this->queueService->createQueue($queueData);

        // Assert
        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertEquals('Test Queue', $queue->name);
        $this->assertEquals('regular', $queue->type);
        $this->assertEquals(100, $queue->max_quantity); // Regular queues now have max_quantity
        $this->assertEquals(100, $queue->remaining_quantity); // remaining_quantity is set to max_quantity
        $this->assertEquals('active', $queue->status);
        $this->assertEquals(0, $queue->current_number);
    }

    public function test_it_can_get_queue_statistics()
    {
        // Arrange
        $queue = Queue::factory()->create(['current_number' => 5]);
        QueueEntry::factory()->count(3)->queued()->create(['queue_id' => $queue->id]);
        QueueEntry::factory()->count(2)->preparing()->create(['queue_id' => $queue->id]);
        QueueEntry::factory()->count(1)->serving()->create(['queue_id' => $queue->id]);

        // Act
        $stats = $this->queueService->getQueueStats($queue);

        // Assert
        $this->assertIsArray($stats);
        $this->assertEquals(6, $stats['total_entries']);
        $this->assertEquals(5, $stats['pending_entries']); // queued + preparing
        $this->assertEquals(0, $stats['completed_entries']);
        $this->assertEquals(5, $stats['current_number']);
        $this->assertContains($stats['status'], ['active', 'paused', 'closed']);
    }

    public function test_it_can_reset_queue()
    {
        // Arrange
        $queue = Queue::factory()->create([
            'current_number' => 10,
            'remaining_quantity' => 25
        ]);

        // Act
        $updatedQueue = $this->queueService->resetQueue($queue);

        // Assert
        $this->assertEquals(0, $updatedQueue->current_number);
    }

    public function test_it_can_pause_queue()
    {
        // Arrange
        $queue = Queue::factory()->active()->create();

        // Act
        $updatedQueue = $this->queueService->pauseQueue($queue);

        // Assert
        $this->assertEquals('paused', $updatedQueue->status);
    }

    public function test_it_can_resume_queue()
    {
        // Arrange
        $queue = Queue::factory()->paused()->create();

        // Act
        $updatedQueue = $this->queueService->resumeQueue($queue);

        // Assert
        $this->assertEquals('active', $updatedQueue->status);
    }

    public function test_it_can_close_queue()
    {
        // Arrange
        $queue = Queue::factory()->active()->create();

        // Act
        $updatedQueue = $this->queueService->closeQueue($queue);

        // Assert
        $this->assertEquals('closed', $updatedQueue->status);
    }

    public function test_it_can_get_next_number()
    {
        // Arrange
        $queue = Queue::factory()->active()->create(['current_number' => 5]);

        // Act
        $nextNumber = $this->queueService->getNextNumber($queue);

        // Assert
        $this->assertEquals(6, $nextNumber);
        $this->assertEquals(6, $queue->fresh()->current_number);
    }

    public function test_it_can_get_next_number_when_queue_inactive()
    {
        // Arrange
        $queue = Queue::factory()->paused()->create(['current_number' => 5]);

        // Act
        $nextNumber = $this->queueService->getNextNumber($queue);

        // Assert
        $this->assertEquals(6, $nextNumber); // Now allows getting next number even when inactive
        $this->assertEquals(6, $queue->fresh()->current_number);
    }

    public function test_it_can_skip_number()
    {
        // Arrange
        $queue = Queue::factory()->active()->create(['current_number' => 5]);

        // Act
        $updatedQueue = $this->queueService->skipNumber($queue);

        // Assert
        $this->assertEquals(6, $updatedQueue->current_number);
    }

    public function test_it_can_recall_number()
    {
        // Arrange
        $queue = Queue::factory()->active()->create(['current_number' => 5]);

        // Act
        $updatedQueue = $this->queueService->recallNumber($queue);

        // Assert
        $this->assertEquals(5, $updatedQueue->current_number);
    }

    public function test_it_can_adjust_stock_for_inventory_queue()
    {
        // Arrange
        $queue = Queue::factory()->inventory()->create([
            'remaining_quantity' => 50,
            'max_quantity' => 100
        ]);

        // Act
        $updatedQueue = $this->queueService->adjustStock($queue, 75);

        // Assert
        $this->assertEquals(75, $updatedQueue->remaining_quantity);
    }

    public function test_it_prevents_stock_adjustment_for_non_inventory_queues()
    {
        // Arrange
        $queue = Queue::factory()->regular()->create();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can only adjust stock for inventory queues');
        $this->queueService->adjustStock($queue, 25);
    }

    public function test_it_can_undo_last_entry_for_inventory_queue()
    {
        // Arrange
        $queue = Queue::factory()->inventory()->create(['current_number' => 5]);
        $lastEntry = QueueEntry::factory()->create([
            'queue_id' => $queue->id,
            'queue_number' => 5,
            'quantity_purchased' => 2
        ]);

        // Act
        $result = $this->queueService->undoLastEntry($queue);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('queue_entries', ['id' => $lastEntry->id]);
    }

    public function test_it_prevents_undo_for_non_inventory_queues()
    {
        // Arrange
        $queue = Queue::factory()->regular()->create();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can only undo entries for inventory queues');
        $this->queueService->undoLastEntry($queue);
    }

    public function test_it_can_add_entry_to_queue()
    {
        // Arrange
        $queue = Queue::factory()->active()->create(['current_number' => 0]);

        // Act
        $entry = $this->queueService->addEntry($queue, [
            'quantity_purchased' => 2
        ]);

        // Assert
        $this->assertInstanceOf(QueueEntry::class, $entry);
        $this->assertEquals($queue->id, $entry->queue_id);
        $this->assertEquals(1, $entry->queue_number);
        $this->assertEquals('queued', $entry->order_status);
    }

    public function test_it_cannot_add_entry_to_inactive_queue()
    {
        // Arrange
        $queue = Queue::factory()->paused()->create();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Queue is not active');
        $this->queueService->addEntry($queue, ['quantity_purchased' => 2]);
    }

    public function test_it_can_update_entry_status()
    {
        // Arrange
        $entry = QueueEntry::factory()->queued()->create();

        // Act
        $updatedEntry = $this->queueService->updateEntryStatus($entry, 'preparing');

        // Assert
        $this->assertEquals('preparing', $updatedEntry->order_status);
    }

    public function test_it_validates_entry_status()
    {
        // Arrange
        $entry = QueueEntry::factory()->create();

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status');
        $this->queueService->updateEntryStatus($entry, 'invalid_status');
    }
}
