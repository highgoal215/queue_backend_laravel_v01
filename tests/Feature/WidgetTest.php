<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ScreenLayout;
use App\Models\Widget;
use App\Models\Queue;
use App\Services\WidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class WidgetTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected ScreenLayout $layout;
    protected Widget $widget;
    protected Queue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->layout = ScreenLayout::factory()->create([
            'name' => 'Test Layout',
            'device_id' => 'device_001',
            'layout_config' => [
                'grid' => [
                    'columns' => 12,
                    'rows' => 8
                ],
                'widgets' => []
            ],
            'is_default' => true
        ]);

        $this->widget = Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'time',
            'position' => json_encode([
                'x' => 0,
                'y' => 0,
                'width' => 4,
                'height' => 2
            ]),
            'settings' => [
                'format' => '24h',
                'timezone' => 'UTC'
            ]
        ]);

        $this->queue = Queue::factory()->create([
            'name' => 'Test Queue',
            'type' => 'regular',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_can_fetch_widget_data_for_device()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/data?device_id=device_001');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'device_id',
                        'layout',
                        'widgets'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'device_id' => 'device_001'
                    ]
                ]);
    }

    /** @test */
    public function it_validates_device_id_when_fetching_widget_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/data');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['device_id']);
    }

    /** @test */
    public function it_can_filter_widgets_by_type()
    {
        Sanctum::actingAs($this->user);

        // Create additional widgets
        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'queue'
        ]);

        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'date'
        ]);

        $response = $this->getJson('/api/widgets/data?device_id=device_001&widget_types[]=time&widget_types[]=queue');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $widgets = $response->json('data.widgets');
        $this->assertCount(2, $widgets);
        $this->assertContains('time', array_column($widgets, 'type'));
        $this->assertContains('queue', array_column($widgets, 'type'));
        $this->assertNotContains('date', array_column($widgets, 'type'));
    }

    /** @test */
    public function it_returns_empty_widgets_for_device_without_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/data?device_id=nonexistent_device');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'device_id' => 'nonexistent_device',
                        'layout' => null,
                        'widgets' => []
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_widget_statistics()
    {
        Sanctum::actingAs($this->user);

        // Create additional widgets for statistics
        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'queue'
        ]);

        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'date'
        ]);

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
                ])
                ->assertJson([
                    'success' => true
                ]);

        $stats = $response->json('data');
        $this->assertEquals(3, $stats['total_widgets']);
        $this->assertArrayHasKey('time', $stats['widgets_by_type']);
        $this->assertArrayHasKey('queue', $stats['widgets_by_type']);
        $this->assertArrayHasKey('date', $stats['widgets_by_type']);
    }

    /** @test */
    public function it_can_get_real_time_widget_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/real-time?device_id=device_001');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'device_id',
                        'timestamp',
                        'widgets'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'device_id' => 'device_001'
                    ]
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertIsArray($data['widgets']);
    }

    /** @test */
    public function it_can_get_preview_data_for_widgets()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/preview?widget_type=time');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true
                ]);
    }

    /** @test */
    public function it_can_get_widgets_by_type()
    {
        Sanctum::actingAs($this->user);

        // Create additional widgets of different types
        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'queue'
        ]);

        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'queue'
        ]);

        $response = $this->getJson('/api/widgets/type/queue');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true
                ]);

        $widgets = $response->json('data');
        $this->assertCount(2, $widgets);
        $this->assertEquals('queue', $widgets[0]['type']);
        $this->assertEquals('queue', $widgets[1]['type']);
    }

    /** @test */
    public function it_validates_widget_type_when_getting_by_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/type/invalid_type');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_can_get_widgets_for_specific_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/layouts/{$this->layout->id}/widgets");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true
                ]);

        $widgets = $response->json('data');
        $this->assertCount(1, $widgets);
        $this->assertEquals('time', $widgets[0]['type']);
    }

    /** @test */
    public function it_can_update_widget_settings()
    {
        Sanctum::actingAs($this->user);

        $newSettings = [
            'format' => '12h',
            'timezone' => 'America/New_York',
            'show_seconds' => true
        ];

        $response = $this->patchJson("/api/widgets/{$this->widget->id}/settings", [
            'settings' => $newSettings
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('widgets', [
            'id' => $this->widget->id,
            'settings->format' => '12h',
            'settings->timezone' => 'America/New_York',
            'settings->show_seconds' => true
        ]);
    }

    /** @test */
    public function it_validates_settings_when_updating_widget()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson("/api/widgets/{$this->widget->id}/settings", [
            'settings' => 'invalid_settings'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['settings']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_widget()
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson('/api/widgets/99999/settings', [
            'settings' => ['format' => '24h']
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_widget_data_generation_for_different_types()
    {
        Sanctum::actingAs($this->user);

        // Create widgets of different types
        $queueWidget = Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'queue',
            'settings' => [
                'queue_id' => $this->queue->id
            ]
        ]);

        $weatherWidget = Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'weather',
            'settings' => [
                'location' => 'New York',
                'units' => 'celsius'
            ]
        ]);

        $response = $this->getJson('/api/widgets/data?device_id=device_001');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $widgets = $response->json('data.widgets');
        $this->assertCount(3, $widgets);

        $widgetTypes = array_column($widgets, 'type');
        $this->assertContains('time', $widgetTypes);
        $this->assertContains('queue', $widgetTypes);
        $this->assertContains('weather', $widgetTypes);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['GET', '/api/widgets/data?device_id=device_001'],
            ['GET', '/api/widgets/stats'],
            ['GET', '/api/widgets/real-time?device_id=device_001'],
            ['GET', '/api/widgets/preview?device_id=device_001'],
            ['GET', '/api/widgets/type/time'],
            ['GET', "/api/layouts/{$this->layout->id}/widgets"],
            ['PATCH', "/api/widgets/{$this->widget->id}/settings"]
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_handles_widget_position_data_correctly()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/data?device_id=device_001');

        $response->assertStatus(200);

        $widgets = $response->json('data.widgets');
        $timeWidget = collect($widgets)->firstWhere('type', 'time');

        $this->assertNotNull($timeWidget);
        $this->assertArrayHasKey('position', $timeWidget);
        $this->assertEquals(0, $timeWidget['position']['x']);
        $this->assertEquals(0, $timeWidget['position']['y']);
        $this->assertEquals(4, $timeWidget['position']['width']);
        $this->assertEquals(2, $timeWidget['position']['height']);
    }

    /** @test */
    public function it_handles_widget_settings_correctly()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/widgets/data?device_id=device_001');

        $response->assertStatus(200);

        $widgets = $response->json('data.widgets');
        $timeWidget = collect($widgets)->firstWhere('type', 'time');

        $this->assertNotNull($timeWidget);
        $this->assertArrayHasKey('settings', $timeWidget);
        $this->assertEquals('24h', $timeWidget['settings']['format']);
        $this->assertEquals('UTC', $timeWidget['settings']['timezone']);
    }
} 