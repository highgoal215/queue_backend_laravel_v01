<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Cashier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class QueueTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_all_queues()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        Queue::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/queues');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'max_quantity',
                        'remaining_quantity',
                        'status',
                        'current_number',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_a_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queueData = [
            'name' => 'Test Food Queue',
            'type' => 'regular',
            'max_quantity' => 100,
            'status' => 'active'
        ];

        // Act
        $response = $this->postJson('/api/queues', $queueData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'max_quantity',
                    'remaining_quantity',
                    'status',
                    'current_number',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test Food Queue',
                    'type' => 'regular',
                    'max_quantity' => 100,
                    'status' => 'active',
                    'current_number' => 0
                ]
            ]);

        $this->assertDatabaseHas('queues', [
            'name' => 'Test Food Queue',
            'type' => 'regular',
            'max_quantity' => 100,
            'status' => 'active',
            'current_number' => 0
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/queues', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'max_quantity']);
    }

    /** @test */
    public function it_can_show_queue_details()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        QueueEntry::factory()->count(3)->create(['queue_id' => $queue->id]);
        Cashier::factory()->count(2)->create(['assigned_queue_id' => $queue->id]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue' => [
                        'id',
                        'name',
                        'type',
                        'max_quantity',
                        'remaining_quantity',
                        'status',
                        'current_number',
                        'entries' => [
                            '*' => [
                                'id',
                                'queue_number',
                                'customer_name',
                                'order_status'
                            ]
                        ],
                        'cashiers' => [
                            '*' => [
                                'id',
                                'name',
                                'status'
                            ]
                        ]
                    ],
                    'statistics' => [
                        'queued_count',
                        'preparing_count',
                        'ready_count',
                        'total_entries',
                        'current_number'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_update_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $updateData = [
            'name' => 'Updated Queue Name',
            'max_quantity' => 150,
            'status' => 'paused'
        ];

        // Act
        $response = $this->putJson("/api/queues/{$queue->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'max_quantity',
                    'remaining_quantity',
                    'status',
                    'current_number'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Queue Name',
                    'max_quantity' => 150,
                    'status' => 'paused'
                ]
            ]);

        $this->assertDatabaseHas('queues', $updateData);
    }

    /** @test */
    public function it_can_delete_queue_without_active_entries()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        QueueEntry::factory()->completed()->create(['queue_id' => $queue->id]);

        // Act
        $response = $this->deleteJson("/api/queues/{$queue->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue deleted successfully'
            ]);

        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    /** @test */
    public function it_cannot_delete_queue_with_active_entries()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        QueueEntry::factory()->queued()->create(['queue_id' => $queue->id]);

        // Act
        $response = $this->deleteJson("/api/queues/{$queue->id}");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete queue with active entries'
            ]);

        $this->assertDatabaseHas('queues', ['id' => $queue->id]);
    }

    /** @test */
    public function it_can_reset_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->inventory()->create([
            'current_number' => 10,
            'max_quantity' => 100,
            'remaining_quantity' => 25
        ]);

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/reset");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue reset successfully'
            ]);

        $queue->refresh();
        $this->assertEquals(0, $queue->current_number);
        $this->assertEquals($queue->max_quantity, $queue->remaining_quantity);
    }

    /** @test */
    public function it_can_pause_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->active()->create();

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/pause");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue paused successfully'
            ]);

        $this->assertEquals('paused', $queue->fresh()->status);
    }

    /** @test */
    public function it_can_resume_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->paused()->create();

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/resume");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue resumed successfully'
            ]);

        $this->assertEquals('active', $queue->fresh()->status);
    }

    /** @test */
    public function it_can_close_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->active()->create();

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/close");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue closed successfully'
            ]);

        $this->assertEquals('closed', $queue->fresh()->status);
    }

    /** @test */
    public function it_can_get_queue_status()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create(['current_number' => 5]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/status");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_number',
                    'status',
                    'total_entries',
                    'queued_count',
                    'preparing_count',
                    'ready_count'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_number' => 5
                ]
            ]);
    }

    /** @test */
    public function it_can_call_next_number()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->active()->create(['current_number' => 5]);
        QueueEntry::factory()->queued()->create([
            'queue_id' => $queue->id,
            'queue_number' => 6
        ]);

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/call-next");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Next number called successfully'
            ]);

        $this->assertEquals(6, $queue->fresh()->current_number);
    }

    /** @test */
    public function it_can_skip_current_number()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->active()->create(['current_number' => 5]);
        QueueEntry::factory()->queued()->create([
            'queue_id' => $queue->id,
            'queue_number' => 6
        ]);

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/skip");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Number skipped successfully'
            ]);

        $this->assertEquals(6, $queue->fresh()->current_number);
    }

    /** @test */
    public function it_can_recall_current_number()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->active()->create(['current_number' => 5]);

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/recall");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Number recalled successfully'
            ]);
    }

    /** @test */
    public function it_can_adjust_stock_for_inventory_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->inventory()->create([
            'remaining_quantity' => 50,
            'max_quantity' => 100
        ]);

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/adjust-stock", [
            'new_quantity' => 25
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stock adjusted successfully'
            ]);

        $this->assertEquals(25, $queue->fresh()->remaining_quantity);
    }

    /** @test */
    public function it_cannot_adjust_stock_for_non_inventory_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->regular()->create();

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/adjust-stock", [
            'new_quantity' => 25
        ]);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Stock adjustment only available for inventory queues'
            ]);
    }

    /** @test */
    public function it_can_undo_last_entry()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->active()->inventory()->create(['current_number' => 5]);
        $lastEntry = QueueEntry::factory()->create([
            'queue_id' => $queue->id,
            'queue_number' => 5,
            'quantity_purchased' => 2
        ]);

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/undo-last-entry");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Last entry undone successfully'
            ]);

        $this->assertEquals(4, $queue->fresh()->current_number);
        $this->assertDatabaseMissing('queue_entries', ['id' => $lastEntry->id]);
    }

    /** @test */
    public function it_can_get_queue_entries()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        QueueEntry::factory()->count(5)->create(['queue_id' => $queue->id]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/entries");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'queue_number',
                        'customer_name',
                        'phone_number',
                        'order_details',
                        'order_status',
                        'estimated_wait_time',
                        'created_at'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_get_queue_analytics()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        QueueEntry::factory()->count(5)->completed()->create(['queue_id' => $queue->id]);
        QueueEntry::factory()->count(3)->cancelled()->create(['queue_id' => $queue->id]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/analytics");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_entries',
                    'completed_count',
                    'cancelled_count',
                    'completion_rate',
                    'average_wait_time',
                    'peak_hours'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_entries' => 8,
                    'completed_count' => 5,
                    'cancelled_count' => 3
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        // Act & Assert
        $this->getJson('/api/queues')->assertStatus(401);
        $this->postJson('/api/queues')->assertStatus(401);
        $this->getJson('/api/queues/1')->assertStatus(401);
        $this->putJson('/api/queues/1')->assertStatus(401);
        $this->deleteJson('/api/queues/1')->assertStatus(401);
    }
}
