<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_register_a_new_user()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $response = $this->postJson('/api/register', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at'
                    ],
                    'token'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_registering()
    {
        // Act
        $response = $this->postJson('/api/register', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function it_validates_email_uniqueness_when_registering()
    {
        // Arrange
        User::factory()->create(['email' => 'john@example.com']);

        // Act
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_password_confirmation_when_registering()
    {
        // Act
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at'
                    ],
                    'token'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'john@example.com'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword'
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_when_logging_in()
    {
        // Act
        $response = $this->postJson('/api/login', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function it_can_get_authenticated_user()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
    }

    /** @test */
    public function it_can_update_authenticated_user()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/user', $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com'
                ]
            ]);

        $this->assertDatabaseHas('users', $updateData);
    }

    /** @test */
    public function it_validates_email_uniqueness_when_updating_user()
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/user', [
            'name' => 'Updated Name',
            'email' => 'other@example.com'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        // Act & Assert
        $this->getJson('/api/user')->assertStatus(401);
        $this->putJson('/api/user')->assertStatus(401);
    }

    /** @test */
    public function it_validates_email_format()
    {
        // Act
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_password_minimum_length()
    {
        // Act
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
} 