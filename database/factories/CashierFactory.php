<?php

namespace Database\Factories;

use App\Models\Cashier;
use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashierFactory extends Factory
{
    protected $model = Cashier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'employee_id' => $this->faker->unique()->numerify('EMP####'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'break']),
            'assigned_queue_id' => null,
            'is_available' => $this->faker->boolean(80),
            'current_customer_id' => null,
            'total_served' => $this->faker->numberBetween(0, 100),
            'average_service_time' => $this->faker->numberBetween(2, 10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_available' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'is_available' => false,
        ]);
    }

    public function onBreak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'break',
            'is_available' => false,
        ]);
    }

    public function assignedToQueue(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_queue_id' => Queue::factory(),
        ]);
    }
} 