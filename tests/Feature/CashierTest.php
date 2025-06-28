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
    public function it_can_update_cashier_with_all_valid_fields()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'is_available' => true,
            'is_active' => true
        ]);
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '9876543210',
            'status' => 'break',
            'is_available' => false,
            'is_active' => false,
            'role' => 'Senior Cashier',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'assigned_queue_id' => $this->queue->id
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
                    'employee_id',
                    'status',
                    'assigned_queue_id',
                    'is_available',
                    'is_active',
                    'current_customer_id',
                    'total_served',
                    'average_service_time',
                    'email',
                    'phone',
                    'role',
                    'shift_start',
                    'shift_end',
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
                'success' => true,
                'message' => 'Cashier updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'phone' => '9876543210',
                    'status' => 'break',
                    'is_available' => false,
                    'is_active' => false,
                    'role' => 'Senior Cashier',
                    'assigned_queue_id' => $this->queue->id
                ]
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('cashiers', [
            'id' => $cashier->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '9876543210',
            'status' => 'break',
            'is_available' => false,
            'is_active' => false,
            'role' => 'Senior Cashier',
            'assigned_queue_id' => $this->queue->id
        ]);
    }

    /** @test */
    public function it_can_update_cashier_with_partial_data()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => 'active'
        ]);
        
        $updateData = [
            'name' => 'Partial Update Name',
            'status' => 'inactive'
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Partial Update Name',
                    'status' => 'inactive'
                ]
            ]);

        // Verify only specified fields were updated
        $this->assertDatabaseHas('cashiers', [
            'id' => $cashier->id,
            'name' => 'Partial Update Name',
            'status' => 'inactive',
            'email' => 'original@example.com' // Should remain unchanged
        ]);
    }

    /** @test */
    public function it_validates_unique_name_when_updating()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $existingCashier = Cashier::factory()->create(['name' => 'Existing Cashier']);
        $cashierToUpdate = Cashier::factory()->create(['name' => 'Original Name']);
        
        $updateData = [
            'name' => 'Existing Cashier' // This name already exists
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashierToUpdate->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_unique_email_when_updating()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $existingCashier = Cashier::factory()->create(['email' => 'existing@example.com']);
        $cashierToUpdate = Cashier::factory()->create(['email' => 'original@example.com']);
        
        $updateData = [
            'email' => 'existing@example.com' // This email already exists
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashierToUpdate->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_allows_same_email_for_same_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create(['email' => 'test@example.com']);
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'test@example.com' // Same email as the cashier
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'test@example.com'
                ]
            ]);
    }

    /** @test */
    public function it_validates_assigned_queue_exists()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        
        $updateData = [
            'assigned_queue_id' => 99999 // Non-existent queue ID
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_queue_id']);
    }

    /** @test */
    public function it_validates_shift_end_after_shift_start()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        
        $updateData = [
            'shift_start' => '17:00',
            'shift_end' => '09:00' // End time before start time
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_end']);
    }

    /** @test */
    public function it_validates_shift_time_format()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        
        $updateData = [
            'shift_start' => '25:00', // Invalid time format
            'shift_end' => '26:00'
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_start', 'shift_end']);
    }

    /** @test */
    public function it_validates_status_values()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        
        $updateData = [
            'status' => 'invalid_status' // Invalid status
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_validates_boolean_fields()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->create();
        
        $updateData = [
            'is_available' => 'not_boolean',
            'is_active' => 'not_boolean'
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_available', 'is_active']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_cashier()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $nonexistentId = 99999;
        
        $updateData = [
            'name' => 'Updated Name'
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$nonexistentId}", $updateData);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_authentication_for_update()
    {
        // Arrange
        $cashier = Cashier::factory()->create();
        
        $updateData = [
            'name' => 'Updated Name'
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_remove_queue_assignment()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $cashier = Cashier::factory()->assignedToQueue()->create();
        
        $updateData = [
            'assigned_queue_id' => null
        ];

        // Act
        $response = $this->putJson("/api/cashiers/{$cashier->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'assigned_queue_id' => null
                ]
            ]);

        $this->assertDatabaseHas('cashiers', [
            'id' => $cashier->id,
            'assigned_queue_id' => null
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

    /** @test */
    public function it_can_create_a_cashier_with_all_specified_parameters()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        $queue = Queue::factory()->create();
        
        $cashierData = [
            'name' => 'John Doe',
            'employee_id' => 'EMP001',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'role' => 'senior_cashier',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'assigned_queue_id' => $queue->id // Optional parameter
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
                    'email',
                    'phone',
                    'role',
                    'shift_start',
                    'shift_end',
                    'assigned_queue_id',
                    'status',
                    'is_active',
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
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'employee_id' => 'EMP001',
                    'email' => 'john.doe@example.com',
                    'phone' => '1234567890',
                    'role' => 'senior_cashier',
                    'assigned_queue_id' => $queue->id,
                    'status' => 'active',
                    'is_active' => true,
                    'is_available' => true
                ]
            ]);

        // Verify the data is stored in database
        $this->assertDatabaseHas('cashiers', [
            'name' => 'John Doe',
            'employee_id' => 'EMP001',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'role' => 'senior_cashier',
            'assigned_queue_id' => $queue->id,
            'status' => 'active',
            'is_active' => true,
            'is_available' => true
        ]);

        // Verify shift times are properly stored
        $cashier = Cashier::where('name', 'John Doe')->first();
        $this->assertEquals('09:00', $cashier->shift_start->format('H:i'));
        $this->assertEquals('17:00', $cashier->shift_end->format('H:i'));
    }

    /** @test */
    public function it_can_create_a_cashier_without_assigned_queue()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $cashierData = [
            'name' => 'Jane Smith',
            'employee_id' => 'EMP002',
            'email' => 'jane.smith@example.com',
            'phone' => '0987654321',
            'role' => 'cashier',
            'shift_start' => '08:00',
            'shift_end' => '16:00'
            // assigned_queue_id is not provided (optional)
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
                    'email',
                    'phone',
                    'role',
                    'shift_start',
                    'shift_end',
                    'assigned_queue_id',
                    'status',
                    'is_active',
                    'is_available',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Jane Smith',
                    'employee_id' => 'EMP002',
                    'email' => 'jane.smith@example.com',
                    'phone' => '0987654321',
                    'role' => 'cashier',
                    'assigned_queue_id' => null,
                    'status' => 'active',
                    'is_active' => true,
                    'is_available' => true
                ]
            ]);

        // Verify the data is stored in database
        $this->assertDatabaseHas('cashiers', [
            'name' => 'Jane Smith',
            'employee_id' => 'EMP002',
            'email' => 'jane.smith@example.com',
            'phone' => '0987654321',
            'role' => 'cashier',
            'assigned_queue_id' => null,
            'status' => 'active',
            'is_active' => true,
            'is_available' => true
        ]);
    }

    /** @test */
    public function it_validates_required_name_field()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $cashierData = [
            'employee_id' => 'EMP003',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'role' => 'cashier',
            'shift_start' => '09:00',
            'shift_end' => '17:00'
            // name is missing (required)
        ];

        // Act
        $response = $this->postJson('/api/cashiers', $cashierData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => 'Cashier name is required.'
            ]);
    }

    /** @test */
    public function it_validates_email_format()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        $cashierData = [
            'name' => 'Test User',
            'email' => 'invalid-email-format', // Invalid email format
            'phone' => '1234567890',
            'role' => 'cashier'
        ];

        // Act
        $response = $this->postJson('/api/cashiers', $cashierData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'message' => 'Please provide a valid email address.'
            ]);
    }
} 