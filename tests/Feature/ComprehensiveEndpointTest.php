<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Cashier;
use App\Models\ScreenLayout;
use App\Models\Widget;
use App\Models\CustomerTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;

class ComprehensiveEndpointTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Queue $queue;
    protected QueueEntry $entry;
    protected Cashier $cashier;
    protected ScreenLayout $layout;
    protected Widget $widget;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->queue = Queue::factory()->create([
            'name' => 'Test Queue',
            'type' => 'regular',
            'max_quantity' => 100,
            'status' => 'active'
        ]);
        $this->entry = QueueEntry::factory()->create(['queue_id' => $this->queue->id]);
        $this->cashier = Cashier::factory()->create([
            'name' => 'Test Cashier',
            'status' => 'active',
            'is_active' => true
        ]);
        $this->layout = ScreenLayout::factory()->create();
        $this->widget = Widget::factory()->create(['screen_layout_id' => $this->layout->id]);
    }

    // ========================================
    // AUTHENTICATION ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_register_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_login_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_authenticated_user()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_authenticated_user()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        $response = $this->putJson('/api/user', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_delete_authenticated_user()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/user');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ========================================
    // QUEUE ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_get_all_queues()
    {
        Sanctum::actingAs($this->user);
        Queue::factory()->count(3)->create();

        $response = $this->getJson('/api/queues');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'type', 'max_quantity', 'status']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_create_queue()
    {
        Sanctum::actingAs($this->user);

        $queueData = [
            'name' => 'New Queue',
            'type' => 'regular',
            'max_quantity' => 50,
            'status' => 'active'
        ];

        $response = $this->postJson('/api/queues', $queueData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'type', 'max_quantity'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_queue_details()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/queues/{$this->queue->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue' => ['id', 'name', 'type', 'max_quantity'],
                    'statistics' => ['total_entries', 'current_number']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_queue()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Queue Name',
            'max_quantity' => 75
        ];

        $response = $this->putJson("/api/queues/{$this->queue->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'max_quantity'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_delete_queue()
    {
        Sanctum::actingAs($this->user);

        // Remove all entries from the queue to allow deletion
        $this->queue->entries()->delete();

        $response = $this->deleteJson("/api/queues/{$this->queue->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_reset_queue()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/reset");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_pause_queue()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/pause");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_resume_queue()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/resume");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_close_queue()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/close");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_call_next_number()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/call-next");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_skip_current_number()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/skip");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_recall_current_number()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/queues/{$this->queue->id}/recall");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_adjust_stock()
    {
        Sanctum::actingAs($this->user);
        $inventoryQueue = Queue::factory()->inventory()->create();

        $response = $this->postJson("/api/queues/{$inventoryQueue->id}/adjust-stock", [
            'new_quantity' => 10
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_undo_last_entry()
    {
        Sanctum::actingAs($this->user);
        $inventoryQueue = Queue::factory()->inventory()->create();
        QueueEntry::factory()->create([
            'queue_id' => $inventoryQueue->id,
            'quantity_purchased' => 2
        ]);

        $response = $this->postJson("/api/queues/{$inventoryQueue->id}/undo-last-entry");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_get_queue_entries()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/queues/{$this->queue->id}/entries");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'queue_number', 'customer_name']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_queue_analytics()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/queues/{$this->queue->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_entries',
                    'completed_count',
                    'average_wait_time'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_queue_status()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/queues/{$this->queue->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['status', 'current_number', 'total_entries'],
                'message'
            ]);
    }

    // ========================================
    // QUEUE ENTRY ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_get_all_entries()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(3)->create();

        $response = $this->getJson('/api/entries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'queue_number', 'customer_name', 'order_status']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_create_entry()
    {
        Sanctum::actingAs($this->user);

        $entryData = [
            'queue_id' => $this->queue->id,
            'customer_name' => 'John Doe',
            'phone_number' => '+1234567890',
            'quantity_purchased' => 1
        ];

        $response = $this->postJson('/api/entries', $entryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'queue_number', 'customer_name'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_entry_details()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        $response = $this->getJson("/api/entries/{$entry->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'queue_number', 'customer_name'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_entry()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        $updateData = [
            'customer_name' => 'Updated Customer',
            'order_status' => 'completed'
        ];

        $response = $this->putJson("/api/entries/{$entry->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'customer_name'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_delete_entry()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        $response = $this->deleteJson("/api/entries/{$entry->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_update_entry_status()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        $statusData = [
            'order_status' => 'completed'
        ];

        $response = $this->patchJson("/api/entries/{$entry->id}/status", $statusData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'order_status'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_cancel_entry()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        $response = $this->postJson("/api/entries/{$entry->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_get_entries_by_status()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(3)->create(['order_status' => 'completed']);

        $response = $this->getJson('/api/entries/status/completed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'order_status']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_active_entries_for_queue()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(3)->create(['queue_id' => $this->queue->id]);

        $response = $this->getJson("/api/queues/{$this->queue->id}/entries/active");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'queue_id']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_next_entry_for_queue()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create(['queue_id' => $this->queue->id]);

        $response = $this->getJson("/api/queues/{$this->queue->id}/entries/next");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_entries_by_cashier()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(3)->create(['cashier_id' => $this->cashier->id]);

        $response = $this->getJson("/api/entries/cashier/{$this->cashier->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'cashier_id']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_entry_statistics()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(5)->create();

        $response = $this->getJson('/api/entries/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_entries',
                    'completed_count',
                    'cancelled_count'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_bulk_update_entry_status()
    {
        Sanctum::actingAs($this->user);
        $entries = QueueEntry::factory()->count(3)->create();

        $bulkData = [
            'entry_ids' => $entries->pluck('id')->toArray(),
            'order_status' => 'completed'
        ];

        $response = $this->postJson('/api/entries/bulk-update-status', $bulkData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_get_entry_timeline()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();

        $response = $this->getJson("/api/entries/{$entry->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'entry_id',
                    'timeline' => [
                        '*' => ['action', 'timestamp', 'details']
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_search_entries()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->create(['customer_name' => 'John Doe']);

        $response = $this->getJson('/api/entries/search?query=John');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'customer_name']
                ],
                'message'
            ]);
    }

    // ========================================
    // CASHIER ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_get_all_cashiers()
    {
        Sanctum::actingAs($this->user);
        Cashier::factory()->count(3)->create();

        $response = $this->getJson('/api/cashiers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'status']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_create_cashier()
    {
        Sanctum::actingAs($this->user);

        $cashierData = [
            'name' => 'New Cashier',
            'status' => 'active',
            'is_active' => true
        ];

        $response = $this->postJson('/api/cashiers', $cashierData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'status'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_cashier_details()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/cashiers/{$this->cashier->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'status'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_cashier()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Cashier Name',
            'status' => 'inactive'
        ];

        $response = $this->putJson("/api/cashiers/{$this->cashier->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'status'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_delete_cashier()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/cashiers/{$this->cashier->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_assign_cashier_to_queue()
    {
        Sanctum::actingAs($this->user);

        $assignData = [
            'assigned_queue_id' => $this->queue->id
        ];

        $response = $this->postJson("/api/cashiers/{$this->cashier->id}/assign", $assignData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'assigned_queue_id'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_set_cashier_active_status()
    {
        Sanctum::actingAs($this->user);

        $statusData = [
            'is_active' => false
        ];

        $response = $this->postJson("/api/cashiers/{$this->cashier->id}/set-active", $statusData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'is_active'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_queues_with_cashiers()
    {
        Sanctum::actingAs($this->user);
        Queue::factory()->count(3)->create();

        $response = $this->getJson('/api/queues-with-cashiers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'cashiers']
                ],
                'message'
            ]);
    }

    // ========================================
    // CUSTOMER TRACKING ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_show_customer_tracking()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();
        $tracking = CustomerTracking::factory()->create(['queue_entry_id' => $entry->id]);

        $response = $this->getJson("/api/tracking/{$entry->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'customer_name', 'status'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_customer_tracking_status()
    {
        Sanctum::actingAs($this->user);
        $entry = QueueEntry::factory()->create();
        $tracking = CustomerTracking::factory()->create(['queue_entry_id' => $entry->id]);

        $statusData = [
            'status' => 'served',
            'notes' => 'Customer served successfully'
        ];

        $response = $this->patchJson("/api/tracking/{$entry->id}/status", $statusData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'status'],
                'message'
            ]);
    }

    // ========================================
    // SCREEN LAYOUT ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_get_all_screen_layouts()
    {
        Sanctum::actingAs($this->user);
        ScreenLayout::factory()->count(3)->create();

        $response = $this->getJson('/api/layouts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'device_id']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_create_screen_layout()
    {
        Sanctum::actingAs($this->user);

        $layoutData = [
            'name' => 'New Layout',
            'device_id' => 'device_001',
            'layout_config' => [
                'grid' => [
                    'columns' => 12,
                    'rows' => 8
                ],
                'widgets' => []
            ],
            'is_default' => false
        ];

        $response = $this->postJson('/api/layouts', $layoutData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'device_id'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_screen_layout_details()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();

        $response = $this->getJson("/api/layouts/{$layout->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'device_id'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_screen_layout()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();

        $updateData = [
            'name' => 'Updated Layout Name',
            'layout_config' => [
                'grid' => [
                    'columns' => 10,
                    'rows' => 6
                ],
                'widgets' => ['updated' => true]
            ]
        ];

        $response = $this->putJson("/api/layouts/{$layout->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_delete_screen_layout()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();

        $response = $this->deleteJson("/api/layouts/{$layout->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_set_layout_as_default()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();

        $response = $this->postJson("/api/layouts/{$layout->id}/set-default");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_duplicate_layout()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();

        $response = $this->postJson("/api/layouts/{$layout->id}/duplicate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_layout_preview()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();

        $response = $this->getJson("/api/layouts/{$layout->id}/preview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'layout',
                    'preview_data'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_layout_by_device_id()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create(['device_id' => 'test_device']);

        $response = $this->getJson('/api/layouts/device/test_device');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'device_id'],
                'message'
            ]);
    }

    // ========================================
    // WIDGET ENDPOINTS
    // ========================================

    /** @test */
    public function it_can_fetch_widget_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/data?device_id=test_device');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'type', 'data']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_widget_statistics()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_widgets',
                    'widgets_by_type',
                    'layouts_with_widgets',
                    'total_layouts',
                    'average_widgets_per_layout'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_real_time_widget_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/real-time?device_id=test_device');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'type', 'real_time_data']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_widget_preview_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/preview?widget_type=queue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'type', 'preview_data']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_widgets_by_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/type/queue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'type', 'settings']
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_update_widget_settings()
    {
        Sanctum::actingAs($this->user);
        $widget = Widget::factory()->create();

        $settingsData = [
            'settings' => ['theme' => 'dark', 'refresh_rate' => 30]
        ];

        $response = $this->patchJson("/api/widgets/{$widget->id}/settings", $settingsData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'settings'],
                'message'
            ]);
    }

    /** @test */
    public function it_can_get_widgets_by_layout()
    {
        Sanctum::actingAs($this->user);
        $layout = ScreenLayout::factory()->create();
        Widget::factory()->count(3)->create(['layout_id' => $layout->id]);

        $response = $this->getJson("/api/layouts/{$layout->id}/widgets");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'layout_id']
                ],
                'message'
            ]);
    }

    // ========================================
    // ERROR HANDLING TESTS
    // ========================================

    /** @test */
    public function it_returns_404_for_nonexistent_resources()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/queues/999999');
        $response->assertStatus(404);

        $response = $this->getJson('/api/entries/999999');
        $response->assertStatus(404);

        $response = $this->getJson('/api/cashiers/999999');
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_422_for_invalid_data()
    {
        Sanctum::actingAs($this->user);

        // Test invalid queue creation
        $response = $this->postJson('/api/queues', [
            'name' => '', // Invalid: empty name
            'type' => 'invalid_type', // Invalid: not in enum
            'max_quantity' => -1 // Invalid: negative number
        ]);
        $response->assertStatus(422);

        // Test invalid entry creation
        $response = $this->postJson('/api/entries', [
            'queue_id' => 999999, // Invalid: non-existent queue
            'customer_name' => '', // Invalid: empty name
            'phone_number' => '', // Invalid: empty phone
            'quantity_purchased' => 0 // Invalid: zero quantity
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_requests()
    {
        // Test without authentication
        $response = $this->getJson('/api/queues');
        $response->assertStatus(401);

        $response = $this->postJson('/api/queues', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/entries');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_query_parameters_correctly()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(5)->create();

        // Test filtering by status
        $response = $this->getJson('/api/entries?status=completed');
        $response->assertStatus(200);

        // Test filtering by date
        $response = $this->getJson('/api/entries?date=' . date('Y-m-d'));
        $response->assertStatus(200);

        // Test filtering by cashier
        $response = $this->getJson('/api/entries?cashier_id=' . $this->cashier->id);
        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_bulk_operations_correctly()
    {
        Sanctum::actingAs($this->user);
        $entries = QueueEntry::factory()->count(3)->create();

        // Test bulk status update
        $response = $this->postJson('/api/entries/bulk-update-status', [
            'entry_ids' => $entries->pluck('id')->toArray(),
            'order_status' => 'completed'
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_empty_results_correctly()
    {
        Sanctum::actingAs($this->user);

        // Test empty queues
        $response = $this->getJson('/api/queues');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);

        // Test empty entries
        $response = $this->getJson('/api/entries');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);
    }

    /** @test */
    public function it_handles_large_data_sets()
    {
        Sanctum::actingAs($this->user);
        QueueEntry::factory()->count(100)->create();

        $response = $this->getJson('/api/entries');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);
    }

    /** @test */
    public function it_handles_special_characters_in_data()
    {
        Sanctum::actingAs($this->user);

        $entryData = [
            'queue_id' => $this->queue->id,
            'customer_name' => 'José María O\'Connor-Smith',
            'phone_number' => '+1-555-123-4567',
            'quantity_purchased' => 1
        ];

        $response = $this->postJson('/api/entries', $entryData);
        $response->assertStatus(201);
    }
} 