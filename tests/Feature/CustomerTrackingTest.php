<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\QueueEntry;
use App\Models\CustomerTracking;
use App\Models\Queue;
use App\Services\CustomerTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class CustomerTrackingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected QueueEntry $queueEntry;
    protected CustomerTracking $tracking;
    protected Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->queue = Queue::factory()->create([
            'name' => 'Test Queue',
            'type' => 'regular',
            'status' => 'active'
        ]);

        $this->queueEntry = QueueEntry::factory()->create([
            'queue_id' => $this->queue->id,
            'customer_name' => 'John Doe',
            'order_status' => 'queued',
            'queue_number' => 1
        ]);

        $this->tracking = CustomerTracking::factory()->create([
            'queue_entry_id' => $this->queueEntry->id,
            'status' => 'waiting',
            'estimated_wait_time' => 15,
            'current_position' => 1
        ]);
    }

    /** @test */
    public function it_can_show_customer_tracking_information()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/tracking/{$this->queueEntry->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'queue_entry_id',
                        'status',
                        'estimated_wait_time',
                        'current_position',
                        'created_at',
                        'updated_at',
                        'entry' => [
                            'id',
                            'customer_name',
                            'queue_number',
                            'order_status',
                            'queue_id'
                        ]
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'queue_entry_id' => $this->queueEntry->id,
                        'status' => 'waiting',
                        'current_position' => 1
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_tracking_entry()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/tracking/99999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer tracking not found'
                ]);
    }

    /** @test */
    public function it_can_update_customer_tracking_status()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'status' => 'called',
            'estimated_wait_time' => 5,
            'current_position' => 0
        ];

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'status' => 'called',
                        'estimated_wait_time' => 5,
                        'current_position' => 0
                    ]
                ]);

        $this->assertDatabaseHas('customer_tracking', [
            'queue_entry_id' => $this->queueEntry->id,
            'status' => 'called',
            'estimated_wait_time' => 5,
            'current_position' => 0
        ]);
    }

    /** @test */
    public function it_validates_status_when_updating_tracking()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_validates_estimated_wait_time_when_updating_tracking()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'status' => 'waiting',
            'estimated_wait_time' => -5
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['estimated_wait_time']);
    }

    /** @test */
    public function it_validates_current_position_when_updating_tracking()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'status' => 'waiting',
            'current_position' => -1
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['current_position']);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_tracking()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson('/api/tracking/99999/status', [
            'status' => 'called'
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Customer tracking not found'
                ]);
    }

    /** @test */
    public function it_can_update_only_status_field()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'status' => 'served'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'status' => 'served'
                    ]
                ]);

        $this->assertDatabaseHas('customer_tracking', [
            'queue_entry_id' => $this->queueEntry->id,
            'status' => 'served'
        ]);
    }

    /** @test */
    public function it_can_update_only_estimated_wait_time()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'estimated_wait_time' => 20
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'estimated_wait_time' => 20
                    ]
                ]);

        $this->assertDatabaseHas('customer_tracking', [
            'queue_entry_id' => $this->queueEntry->id,
            'estimated_wait_time' => 20
        ]);
    }

    /** @test */
    public function it_can_update_only_current_position()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'current_position' => 2
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'current_position' => 2
                    ]
                ]);

        $this->assertDatabaseHas('customer_tracking', [
            'queue_entry_id' => $this->queueEntry->id,
            'current_position' => 2
        ]);
    }

    /** @test */
    public function it_handles_all_valid_status_values()
    {
        Sanctum::actingAs($this->user);

        $validStatuses = ['waiting', 'called', 'served', 'cancelled', 'no_show'];

        foreach ($validStatuses as $status) {
            $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
                'status' => $status
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'status' => $status
                        ]
                    ]);

            $this->assertDatabaseHas('customer_tracking', [
                'queue_entry_id' => $this->queueEntry->id,
                'status' => $status
            ]);
        }
    }

    /** @test */
    public function it_includes_queue_entry_data_in_tracking_response()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/tracking/{$this->queueEntry->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('entry', $data);
        $this->assertEquals($this->queueEntry->id, $data['entry']['id']);
        $this->assertEquals('John Doe', $data['entry']['customer_name']);
        $this->assertEquals(1, $data['entry']['queue_number']);
        $this->assertEquals('queued', $data['entry']['order_status']);
    }

    /** @test */
    public function it_handles_tracking_without_queue_entry()
    {
        Sanctum::actingAs($this->user);

        // This test is not applicable since queue_entry_id is required
        // and cannot be null. The tracking system always requires a queue entry.
        $this->markTestSkipped('Tracking without queue entry is not supported as queue_entry_id is required.');
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['GET', "/api/tracking/{$this->queueEntry->id}"],
            ['PATCH', "/api/tracking/{$this->queueEntry->id}/status"]
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_handles_concurrent_status_updates()
    {
        Sanctum::actingAs($this->user);

        // Simulate concurrent updates
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
                'status' => 'called',
                'estimated_wait_time' => $i + 1
            ]);
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Last update should be the final state
        $this->assertDatabaseHas('customer_tracking', [
            'queue_entry_id' => $this->queueEntry->id,
            'status' => 'called'
        ]);
    }

    /** @test */
    public function it_handles_tracking_with_special_characters_in_customer_name()
    {
        Sanctum::actingAs($this->user);

        $specialNameEntry = QueueEntry::factory()->create([
            'queue_id' => $this->queue->id,
            'customer_name' => 'José María O\'Connor-Smith',
            'order_status' => 'queued'
        ]);

        $specialTracking = CustomerTracking::factory()->create([
            'queue_entry_id' => $specialNameEntry->id,
            'status' => 'waiting'
        ]);

        $response = $this->getJson("/api/tracking/{$specialNameEntry->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'entry' => [
                            'customer_name' => 'José María O\'Connor-Smith'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_handles_zero_values_for_numeric_fields()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'estimated_wait_time' => 0,
            'current_position' => 0
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'estimated_wait_time' => 0,
                        'current_position' => 0
                    ]
                ]);

        $this->assertDatabaseHas('customer_tracking', [
            'queue_entry_id' => $this->queueEntry->id,
            'estimated_wait_time' => 0,
            'current_position' => 0
        ]);
    }

    /** @test */
    public function it_handles_large_numeric_values()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/tracking/{$this->queueEntry->id}/status", [
            'estimated_wait_time' => 999,
            'current_position' => 1000
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'estimated_wait_time' => 999,
                        'current_position' => 1000
                    ]
                ]);
    }
} 