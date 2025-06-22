<?php

namespace Database\Factories;

use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueFactory extends Factory
{
    protected $model = Queue::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['regular', 'inventory']),
            'max_quantity' => $this->faker->numberBetween(50, 200),
            'remaining_quantity' => $this->faker->numberBetween(0, 50),
            'status' => $this->faker->randomElement(['active', 'paused', 'closed']),
            'current_number' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'regular',
        ]);
    }

    public function inventory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'inventory',
        ]);
    }
} 