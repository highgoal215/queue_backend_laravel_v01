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

class QueueEntryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->queue = Queue::factory()->active()->regular()->create();
    }

    /** @test */
    public function it_can_list_all_entries()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(5)->create();

        // Act
        $response = $this->getJson('/api/entries');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'queue_id',
                        'queue_number',
                        'customer_name',
                        'phone_number',
                        'order_details',
                        'order_status',
                        'estimated_wait_time',
                        'cashier_id',
                        'notes',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_create_a_queue_entry()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entryData = [
            'queue_id' => $this->queue->id,
            'customer_name' => 'John Doe',
            'phone_number' => '+1234567890',
            'order_details' => json_encode([
                'items' => [
                    ['name' => 'Burger', 'quantity' => 2],
                    ['name' => 'Fries', 'quantity' => 1]
                ],
                'total' => 25.50
            ]),
            'estimated_wait_time' => 15,
            'notes' => 'Extra cheese please',
            'quantity_purchased' => 3
        ];

        // Act
        $response = $this->postJson('/api/entries', $entryData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'queue_id',
                    'queue_number',
                    'customer_name',
                    'phone_number',
                    'order_details',
                    'order_status',
                    'estimated_wait_time',
                    'notes',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_name' => 'John Doe',
                    'phone_number' => '+1234567890',
                    'estimated_wait_time' => 15,
                    'notes' => 'Extra cheese please'
                ]
            ]);

        $this->assertDatabaseHas('queue_entries', [
            'customer_name' => 'John Doe',
            'phone_number' => '+1234567890',
            'estimated_wait_time' => 15,
            'notes' => 'Extra cheese please'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_entry()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/entries', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['queue_id', 'customer_name', 'phone_number']);
    }

    /** @test */
    public function it_can_show_entry_details()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        // Act
        $response = $this->getJson("/api/entries/{$entry->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'queue_id',
                    'queue_number',
                    'customer_name',
                    'phone_number',
                    'order_details',
                    'order_status',
                    'estimated_wait_time',
                    'cashier_id',
                    'notes',
                    'created_at',
                    'updated_at',
                    'queue' => [
                        'id',
                        'name',
                        'type'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_update_entry()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();
        $updateData = [
            'customer_name' => 'Jane Smith',
            'estimated_wait_time' => 20,
            'notes' => 'Updated notes'
        ];

        // Act
        $response = $this->putJson("/api/entries/{$entry->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'customer_name',
                    'estimated_wait_time',
                    'notes'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer_name' => 'Jane Smith',
                    'estimated_wait_time' => 20,
                    'notes' => 'Updated notes'
                ]
            ]);

        $this->assertDatabaseHas('queue_entries', $updateData);
    }

    /** @test */
    public function it_can_delete_entry()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        // Act
        $response = $this->deleteJson("/api/entries/{$entry->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue entry deleted successfully'
            ]);

        $this->assertDatabaseMissing('queue_entries', ['id' => $entry->id]);
    }

    /** @test */
    public function it_can_update_entry_status()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->queued()->create();

        // Act
        $response = $this->patchJson("/api/entries/{$entry->id}/status", [
            'order_status' => 'preparing'
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entry status updated successfully'
            ]);

        $this->assertEquals('preparing', $entry->fresh()->order_status);
    }

    /** @test */
    public function it_can_cancel_entry()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create([
            'queue_id' => Queue::factory()->regular()->active()->create()->id
        ]);

        // Act
        $response = $this->postJson("/api/entries/{$entry->id}/cancel");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Queue entry cancelled successfully'
            ]);

        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'order_status' => 'cancelled'
        ]);
    }

    /** @test */
    public function it_can_get_entry_timeline()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        // Act
        $response = $this->getJson("/api/entries/{$entry->id}/timeline");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'entry_id',
                    'timeline' => [
                        '*' => [
                            'action',
                            'timestamp',
                            'details'
                        ]
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_get_entries_by_status()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(3)->queued()->create();
        QueueEntry::factory()->count(2)->preparing()->create();

        // Act
        $response = $this->getJson('/api/entries/status/queued');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'queue_number',
                        'customer_name',
                        'order_status'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_entries_by_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        QueueEntry::factory()->count(3)->withCashier()->create(['cashier_id' => $cashier->id]);

        // Act
        $response = $this->getJson("/api/entries/cashier/{$cashier->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'queue_number',
                        'customer_name',
                        'cashier_id'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_entry_statistics()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(5)->completed()->create();
        QueueEntry::factory()->count(3)->cancelled()->create();
        QueueEntry::factory()->count(2)->queued()->create();

        // Act
        $response = $this->getJson('/api/entries/stats');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_entries',
                    'completed_count',
                    'cancelled_count',
                    'queued_count',
                    'preparing_count',
                    'ready_count',
                    'completion_rate',
                    'average_wait_time'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_entries' => 10,
                    'completed_count' => 5,
                    'cancelled_count' => 3,
                    'queued_count' => 2
                ]
            ]);
    }

    /** @test */
    public function it_can_search_entries()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->create(['customer_name' => 'John Doe']);
        QueueEntry::factory()->create(['customer_name' => 'Jane Smith']);

        // Act
        $response = $this->getJson('/api/entries/search?query=John');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'customer_name',
                        'queue_number'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_bulk_update_entry_status()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $entries = QueueEntry::factory()->count(3)->queued()->create();
        $entryIds = $entries->pluck('id')->toArray();

        // Act
        $response = $this->postJson('/api/entries/bulk-update-status', [
            'entry_ids' => $entryIds,
            'order_status' => 'preparing'
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Entries updated successfully'
            ]);

        foreach ($entries as $entry) {
            $this->assertEquals('preparing', $entry->fresh()->order_status);
        }
    }

    /** @test */
    public function it_can_get_active_entries_for_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(3)->queued()->create(['queue_id' => $this->queue->id]);
        QueueEntry::factory()->count(2)->completed()->create(['queue_id' => $this->queue->id]);

        // Act
        $response = $this->getJson("/api/queues/{$this->queue->id}/entries/active");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'queue_number',
                        'customer_name',
                        'order_status'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_next_entry_for_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->queued()->create([
            'queue_id' => $this->queue->id,
            'queue_number' => 1
        ]);

        // Act
        $response = $this->getJson("/api/queues/{$this->queue->id}/entries/next");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'queue_number',
                    'customer_name',
                    'order_status'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_number' => 1
                ]
            ]);
    }

    /** @test */
    public function it_returns_null_when_no_next_entry_available()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->getJson("/api/queues/{$this->queue->id}/entries/next");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'message' => 'No entries in queue'
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        // Act & Assert
        $this->getJson('/api/entries')->assertStatus(401);
        $this->postJson('/api/entries')->assertStatus(401);
        $this->getJson('/api/entries/1')->assertStatus(401);
        $this->putJson('/api/entries/1')->assertStatus(401);
        $this->deleteJson('/api/entries/1')->assertStatus(401);
    }
}
