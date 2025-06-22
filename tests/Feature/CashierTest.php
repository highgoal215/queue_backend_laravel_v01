<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Queue;
use App\Models\Cashier;
use App\Models\QueueEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class CashierTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->queue = Queue::factory()->create();
    }

    /** @test */
    public function it_can_list_all_cashiers()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        Cashier::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/cashiers');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'employee_id',
                        'status',
                        'assigned_queue_id',
                        'is_available',
                        'current_customer_id',
                        'total_served',
                        'average_service_time',
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
    public function it_can_create_a_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashierData = [
            'name' => 'John Cashier',
            'employee_id' => 'EMP1234',
            'status' => 'active',
            'is_available' => true
        ];

        // Act
        $response = $this->postJson('/api/cashiers', $cashierData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'employee_id',
                    'status',
                    'assigned_queue_id',
                    'is_available',
                    'current_customer_id',
                    'total_served',
                    'average_service_time',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Cashier',
                    'employee_id' => 'EMP1234',
                    'status' => 'active',
                    'is_available' => true
                ]
            ]);

        $this->assertDatabaseHas('cashiers', $cashierData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/cashiers', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_can_show_cashier_details()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->assignedToQueue()->create();

        // Act
        $response = $this->getJson("/api/cashiers/{$cashier->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'employee_id',
                    'status',
                    'assigned_queue_id',
                    'is_available',
                    'current_customer_id',
                    'total_served',
                    'average_service_time',
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
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function it_can_update_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        $updateData = [
            'name' => 'Jane Cashier',
            'status' => 'break',
            'is_available' => false
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'status',
                    'is_available'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Jane Cashier',
                    'status' => 'break',
                    'is_available' => false
                ]
            ]);

        $this->assertDatabaseHas('cashiers', [
            'id' => $cashier->id,
            'name' => 'Jane Cashier',
            'status' => 'break',
            'is_available' => false
        ]);
    }

    /** @test */
    public function it_can_delete_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();

        // Act
        $response = $this->deleteJson("/api/cashiers/{$cashier->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cashier deleted successfully'
            ]);

        $this->assertDatabaseMissing('cashiers', ['id' => $cashier->id]);
    }

    /** @test */
    public function it_can_assign_cashier_to_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        $queue = Queue::factory()->create();

        // Act
        $response = $this->postJson("/api/cashiers/{$cashier->id}/assign", [
            'assigned_queue_id' => $queue->id
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cashier assigned to queue successfully'
            ]);
        $this->assertDatabaseHas('cashiers', [
            'id' => $cashier->id,
            'assigned_queue_id' => $queue->id
        ]);
    }

    /** @test */
    public function it_can_set_cashier_active_status()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->inactive()->create();

        // Act
        $response = $this->postJson("/api/cashiers/{$cashier->id}/set-active", [
            'is_active' => true
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cashier status updated successfully'
            ]);
        $this->assertTrue($cashier->fresh()->is_available);
        $this->assertEquals('active', $cashier->fresh()->status);
    }

    /** @test */
    public function it_can_get_queues_with_cashiers()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        Cashier::factory()->count(2)->create(['assigned_queue_id' => $queue->id]);

        // Act
        $response = $this->getJson('/api/queues-with-cashiers');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'status',
                        'cashiers' => [
                            '*' => [
                                'id',
                                'name',
                                'employee_id',
                                'status',
                                'is_available'
                            ]
                        ]
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        // Act & Assert
        $this->getJson('/api/cashiers')->assertStatus(401);
        $this->postJson('/api/cashiers')->assertStatus(401);
        $this->getJson('/api/cashiers/1')->assertStatus(401);
        $this->putJson('/api/cashiers/1')->assertStatus(401);
        $this->deleteJson('/api/cashiers/1')->assertStatus(401);
    }
} 