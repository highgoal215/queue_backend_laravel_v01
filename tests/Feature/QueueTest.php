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
        $queue = Queue::factory()->create();

        $endpoints = [
            'GET /api/queues' => '/api/queues',
            'POST /api/queues' => '/api/queues',
            'GET /api/queues/{id}' => "/api/queues/{$queue->id}",
            'PUT /api/queues/{id}' => "/api/queues/{$queue->id}",
            'DELETE /api/queues/{id}' => "/api/queues/{$queue->id}",
        ];

        foreach ($endpoints as $name => $endpoint) {
            $method = str_contains($name, 'GET') ? 'getJson' : (str_contains($name, 'POST') ? 'postJson' : (str_contains($name, 'PUT') ? 'putJson' : 'deleteJson'));
            $response = $this->$method($endpoint);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_can_get_overall_queue_statistics()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        // Create queues with different statuses
        $activeQueue = Queue::factory()->create(['status' => 'active']);
        $pausedQueue = Queue::factory()->create(['status' => 'paused']);
        $closedQueue = Queue::factory()->create(['status' => 'closed']);

        // Create entries with different statuses
        QueueEntry::factory()->count(5)->create([
            'queue_id' => $activeQueue->id,
            'order_status' => 'completed'
        ]);
        QueueEntry::factory()->count(3)->create([
            'queue_id' => $activeQueue->id,
            'order_status' => 'queued'
        ]);
        QueueEntry::factory()->count(2)->create([
            'queue_id' => $pausedQueue->id,
            'order_status' => 'cancelled'
        ]);

        // Act
        $response = $this->getJson('/api/queues/stats');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_queues',
                    'active_queues',
                    'paused_queues',
                    'closed_queues',
                    'total_entries',
                    'completed_entries',
                    'pending_entries',
                    'cancelled_entries',
                    'completion_rate',
                    'average_wait_time'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_queues' => 3,
                    'active_queues' => 1,
                    'paused_queues' => 1,
                    'closed_queues' => 1,
                    'total_entries' => 10,
                    'completed_entries' => 5,
                    'pending_entries' => 3,
                    'cancelled_entries' => 2,
                    'completion_rate' => 50.0
                ]
            ]);
    }

    /** @test */
    public function it_can_get_active_entries_for_specific_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $cashier = Cashier::factory()->create();

        // Create entries with different statuses
        $activeEntries = QueueEntry::factory()->count(3)->create([
            'queue_id' => $queue->id,
            'order_status' => 'queued',
            'cashier_id' => $cashier->id
        ]);
        QueueEntry::factory()->count(2)->create([
            'queue_id' => $queue->id,
            'order_status' => 'completed'
        ]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/active-entries");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue_id',
                    'queue_name',
                    'active_entries' => [
                        '*' => [
                            'id',
                            'queue_number',
                            'customer_name',
                            'order_details',
                            'order_status',
                            'estimated_wait_time',
                            'created_at',
                            'cashier' => [
                                'id',
                                'name'
                            ],
                            'tracking'
                        ]
                    ],
                    'count'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_name' => $queue->name,
                    'count' => 3
                ]
            ]);
    }

    /** @test */
    public function it_can_get_completed_entries_for_specific_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $cashier = Cashier::factory()->create();

        // Create completed entries
        $completedEntries = QueueEntry::factory()->count(5)->create([
            'queue_id' => $queue->id,
            'order_status' => 'completed',
            'cashier_id' => $cashier->id,
            'estimated_wait_time' => 15
        ]);

        // Create non-completed entries
        QueueEntry::factory()->count(3)->create([
            'queue_id' => $queue->id,
            'order_status' => 'queued'
        ]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/completed-entries");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue_id',
                    'queue_name',
                    'completed_entries' => [
                        '*' => [
                            'id',
                            'queue_number',
                            'customer_name',
                            'order_details',
                            'quantity_purchased',
                            'estimated_wait_time',
                            'completed_at',
                            'created_at',
                            'cashier' => [
                                'id',
                                'name'
                            ]
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_name' => $queue->name,
                    'pagination' => [
                        'total' => 5
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_completed_entries_with_date_filter()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $today = now()->format('Y-m-d');

        // Create entries for today
        QueueEntry::factory()->count(3)->create([
            'queue_id' => $queue->id,
            'order_status' => 'completed',
            'created_at' => now()
        ]);

        // Create entries for yesterday
        QueueEntry::factory()->count(2)->create([
            'queue_id' => $queue->id,
            'order_status' => 'completed',
            'created_at' => now()->subDay()
        ]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/completed-entries?date={$today}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pagination' => [
                        'total' => 3
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_wait_times_for_specific_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();

        // Create entries with wait times
        QueueEntry::factory()->count(5)->create([
            'queue_id' => $queue->id,
            'estimated_wait_time' => 10,
            'created_at' => now()
        ]);
        QueueEntry::factory()->count(3)->create([
            'queue_id' => $queue->id,
            'estimated_wait_time' => 20,
            'created_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/queues/{$queue->id}/wait-times");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue_id',
                    'queue_name',
                    'period',
                    'total_entries',
                    'average_wait_time',
                    'min_wait_time',
                    'max_wait_time',
                    'hourly_trends',
                    'wait_time_distribution' => [
                        'under_5_min',
                        '5_to_10_min',
                        '10_to_15_min',
                        '15_to_20_min',
                        'over_20_min'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'queue_name' => $queue->name,
                    'period' => 'today',
                    'total_entries' => 8,
                    'average_wait_time' => 13.75,
                    'min_wait_time' => 10,
                    'max_wait_time' => 20
                ]
            ]);
    }

    /** @test */
    public function it_can_get_wait_times_with_different_periods()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();

        // Create entries for different periods
        QueueEntry::factory()->count(3)->create([
            'queue_id' => $queue->id,
            'estimated_wait_time' => 15,
            'created_at' => now()
        ]);

        // Act - Test week period
        $response = $this->getJson("/api/queues/{$queue->id}/wait-times?period=week");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'period' => 'week',
                    'total_entries' => 3
                ]
            ]);
    }

    /** @test */
    public function it_can_bulk_update_queue_entries()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $cashier = Cashier::factory()->create();
        
        $entries = QueueEntry::factory()->count(3)->create([
            'queue_id' => $queue->id,
            'order_status' => 'queued'
        ]);

        $entryIds = $entries->pluck('id')->toArray();
        $updateData = [
            'entry_ids' => $entryIds,
            'updates' => [
                'order_status' => 'preparing',
                'cashier_id' => $cashier->id,
                'estimated_wait_time' => 15
            ]
        ];

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/bulk-update", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue_id',
                    'updated_entries' => [
                        '*' => [
                            'id',
                            'order_status',
                            'cashier_id',
                            'estimated_wait_time'
                        ]
                    ],
                    'count'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_id' => $queue->id,
                    'count' => 3
                ]
            ]);

        // Verify database updates
        foreach ($entries as $entry) {
            $this->assertDatabaseHas('queue_entries', [
                'id' => $entry->id,
                'order_status' => 'preparing',
                'cashier_id' => $cashier->id,
                'estimated_wait_time' => 15
            ]);
        }
    }

    /** @test */
    public function it_validates_bulk_update_data()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $entries = QueueEntry::factory()->count(2)->create(['queue_id' => $queue->id]);

        $invalidData = [
            'entry_ids' => $entries->pluck('id')->toArray(),
            'updates' => [
                'order_status' => 'invalid_status',
                'estimated_wait_time' => -5
            ]
        ];

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/bulk-update", $invalidData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['updates.order_status', 'updates.estimated_wait_time']);
    }

    /** @test */
    public function it_cannot_bulk_update_entries_from_different_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue1 = Queue::factory()->create();
        $queue2 = Queue::factory()->create();
        
        $entry1 = QueueEntry::factory()->create(['queue_id' => $queue1->id]);
        $entry2 = QueueEntry::factory()->create(['queue_id' => $queue2->id]);

        $updateData = [
            'entry_ids' => [$entry1->id, $entry2->id],
            'updates' => [
                'order_status' => 'preparing'
            ]
        ];

        // Act
        $response = $this->postJson("/api/queues/{$queue1->id}/bulk-update", $updateData);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Some entries do not belong to this queue'
            ]);
    }

    /** @test */
    public function it_can_bulk_update_with_partial_data()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        $entries = QueueEntry::factory()->count(2)->create([
            'queue_id' => $queue->id,
            'order_status' => 'queued'
        ]);

        $updateData = [
            'entry_ids' => $entries->pluck('id')->toArray(),
            'updates' => [
                'order_status' => 'preparing'
            ]
        ];

        // Act
        $response = $this->postJson("/api/queues/{$queue->id}/bulk-update", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 2
                ]
            ]);

        // Verify only status was updated
        foreach ($entries as $entry) {
            $this->assertDatabaseHas('queue_entries', [
                'id' => $entry->id,
                'order_status' => 'preparing'
            ]);
        }
    }
}
