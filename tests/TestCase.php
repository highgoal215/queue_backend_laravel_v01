<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a default user for tests that need authentication
        $this->user = User::factory()->create();
    }

    /**
     * Authenticate as a user for API tests
     */
    protected function authenticate(): void
    {
        Sanctum::actingAs($this->user);
    }

    /**
     * Create and authenticate as a new user
     */
    protected function authenticateAs(User $user): void
    {
        Sanctum::actingAs($user);
    }

    /**
     * Assert that the response has the standard API structure
     */
    protected function assertApiResponse($response, bool $success = true, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson(['success' => $success]);
    }

    /**
     * Assert that the response has the standard API data structure
     */
    protected function assertApiDataResponse($response, bool $success = true, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ])
            ->assertJson(['success' => $success]);
    }

    /**
     * Assert that the response has the standard API collection structure
     */
    protected function assertApiCollectionResponse($response, bool $success = true, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ])
            ->assertJson(['success' => $success]);
    }
}
