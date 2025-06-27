<?php

namespace Database\Factories;

use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Queue>
 */
class QueueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['regular', 'inventory']),
            'max_quantity' => $this->faker->numberBetween(50, 200),
            'remaining_quantity' => $this->faker->numberBetween(0, 200),
            'status' => $this->faker->randomElement(['active', 'paused', 'closed']),
            'current_number' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the queue is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the queue is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Indicate that the queue is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Indicate that the queue is an inventory queue.
     */
    public function inventory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'inventory',
        ]);
    }

    /**
     * Indicate that the queue is a regular queue.
     */
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'regular',
        ]);
    }
} 