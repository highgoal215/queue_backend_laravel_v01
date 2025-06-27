<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ScreenLayout;
use App\Models\Widget;
use App\Services\LayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ScreenLayoutTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected ScreenLayout $layout;
    protected array $validLayoutData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->validLayoutData = [
            'name' => 'Test Layout',
            'device_id' => 'device_001',
            'layout_config' => [
                'grid' => [
                    'columns' => 12,
                    'rows' => 8
                ],
                'widgets' => [
                    [
                        'type' => 'time',
                        'position' => [
                            'x' => 0,
                            'y' => 0,
                            'width' => 4,
                            'height' => 2
                        ],
                        'settings' => [
                            'format' => '24h',
                            'timezone' => 'UTC'
                        ]
                    ],
                    [
                        'type' => 'queue',
                        'position' => [
                            'x' => 4,
                            'y' => 0,
                            'width' => 8,
                            'height' => 4
                        ],
                        'settings' => [
                            'queue_id' => 1,
                            'show_current' => true
                        ]
                    ]
                ]
            ],
            'is_default' => true
        ];

        $this->layout = ScreenLayout::factory()->create([
            'name' => 'Existing Layout',
            'device_id' => 'device_001',
            'layout_config' => [
                'grid' => [
                    'columns' => 12,
                    'rows' => 6
                ],
                'widgets' => []
            ],
            'is_default' => false
        ]);
    }

    /** @test */
    public function it_can_list_all_screen_layouts()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/layouts');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_list_layouts_with_filters()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/layouts?device_id=device_001&is_default=true');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    /** @test */
    public function it_can_create_a_new_screen_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/layouts', $this->validLayoutData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'device_id',
                        'layout_config',
                        'is_default',
                        'created_at',
                        'updated_at',
                        'widgets'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'Test Layout',
                        'device_id' => 'device_001',
                        'is_default' => true
                    ]
                ]);

        $this->assertDatabaseHas('screen_layouts', [
            'name' => 'Test Layout',
            'device_id' => 'device_001',
            'is_default' => true
        ]);

        $this->assertDatabaseHas('widgets', [
            'type' => 'time'
        ]);

        $this->assertDatabaseHas('widgets', [
            'type' => 'queue'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/layouts', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'device_id', 'layout_config']);
    }

    /** @test */
    public function it_validates_widget_configuration_when_creating_layout()
    {
        Sanctum::actingAs($this->user);

        $invalidData = $this->validLayoutData;
        $invalidData['layout_config']['widgets'][0]['type'] = 'invalid_type';

        $response = $this->postJson('/api/layouts', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['layout_config.widgets.0.type']);
    }

    /** @test */
    public function it_can_show_a_specific_screen_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/layouts/{$this->layout->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'device_id',
                        'layout_config',
                        'is_default',
                        'created_at',
                        'updated_at',
                        'widgets'
                    ],
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $this->layout->id,
                        'name' => 'Existing Layout'
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/layouts/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_a_screen_layout()
    {
        Sanctum::actingAs($this->user);

        $updateData = [
            'name' => 'Updated Layout Name',
            'layout_config' => [
                'grid' => [
                    'columns' => 10,
                    'rows' => 8
                ],
                'widgets' => [
                    [
                        'type' => 'date',
                        'position' => [
                            'x' => 0,
                            'y' => 0,
                            'width' => 3,
                            'height' => 2
                        ],
                        'settings' => [
                            'format' => 'Y-m-d'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->putJson("/api/layouts/{$this->layout->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'Updated Layout Name'
                    ]
                ]);

        $this->assertDatabaseHas('screen_layouts', [
            'id' => $this->layout->id,
            'name' => 'Updated Layout Name'
        ]);
    }

    /** @test */
    public function it_can_delete_a_screen_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/layouts/{$this->layout->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Screen layout deleted successfully'
                ]);

        $this->assertDatabaseMissing('screen_layouts', [
            'id' => $this->layout->id
        ]);
    }

    /** @test */
    public function it_cannot_delete_default_layout()
    {
        Sanctum::actingAs($this->user);

        $defaultLayout = ScreenLayout::factory()->create([
            'device_id' => 'device_002',
            'is_default' => true
        ]);

        $response = $this->deleteJson("/api/layouts/{$defaultLayout->id}");

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function it_can_get_layout_by_device_id()
    {
        Sanctum::actingAs($this->user);

        $defaultLayout = ScreenLayout::factory()->create([
            'device_id' => 'device_003',
            'is_default' => true
        ]);

        $response = $this->getJson('/api/layouts/device/device_003');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $defaultLayout->id,
                        'device_id' => 'device_003',
                        'is_default' => true
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_device_without_default_layout()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/layouts/device/nonexistent_device');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No default layout found for this device'
                ]);
    }

    /** @test */
    public function it_can_set_layout_as_default()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/layouts/{$this->layout->id}/set-default");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $this->layout->id,
                        'is_default' => true
                    ]
                ]);

        $this->assertDatabaseHas('screen_layouts', [
            'id' => $this->layout->id,
            'is_default' => true
        ]);
    }

    /** @test */
    public function it_unsets_other_defaults_when_setting_new_default()
    {
        Sanctum::actingAs($this->user);

        $existingDefault = ScreenLayout::factory()->create([
            'device_id' => $this->layout->device_id,
            'is_default' => true
        ]);

        $response = $this->postJson("/api/layouts/{$this->layout->id}/set-default");

        $response->assertStatus(200);

        $this->assertDatabaseHas('screen_layouts', [
            'id' => $this->layout->id,
            'is_default' => true
        ]);

        $this->assertDatabaseHas('screen_layouts', [
            'id' => $existingDefault->id,
            'is_default' => false
        ]);
    }

    /** @test */
    public function it_can_duplicate_a_layout()
    {
        Sanctum::actingAs($this->user);

        // Add some widgets to the layout
        Widget::factory()->create([
            'screen_layout_id' => $this->layout->id,
            'type' => 'time'
        ]);

        $response = $this->postJson("/api/layouts/{$this->layout->id}/duplicate");

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'Existing Layout (Copy)',
                        'is_default' => false
                    ]
                ]);

        $this->assertDatabaseHas('screen_layouts', [
            'name' => 'Existing Layout (Copy)',
            'device_id' => $this->layout->device_id
        ]);

        // Check that widgets were duplicated
        $duplicatedLayout = ScreenLayout::where('name', 'Existing Layout (Copy)')->first();
        $this->assertTrue($duplicatedLayout->widgets()->count() > 0);
    }

    /** @test */
    public function it_can_get_layout_preview_data()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/layouts/{$this->layout->id}/preview");

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
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['GET', '/api/layouts'],
            ['POST', '/api/layouts'],
            ['GET', "/api/layouts/{$this->layout->id}"],
            ['PUT', "/api/layouts/{$this->layout->id}"],
            ['DELETE', "/api/layouts/{$this->layout->id}"],
            ['POST', "/api/layouts/{$this->layout->id}/set-default"],
            ['POST', "/api/layouts/{$this->layout->id}/duplicate"],
            ['GET', "/api/layouts/{$this->layout->id}/preview"],
            ['GET', '/api/layouts/device/device_001']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    }
} 