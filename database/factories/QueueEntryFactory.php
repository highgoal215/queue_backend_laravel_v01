<?php

namespace Database\Factories;

use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Cashier;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueEntryFactory extends Factory
{
    protected $model = QueueEntry::class;

    public function definition(): array
    {
        return [
            'queue_id' => Queue::factory(),
            'customer_name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'order_details' => json_encode([
                'items' => [
                    ['name' => $this->faker->word(), 'quantity' => $this->faker->numberBetween(1, 5)],
                    ['name' => $this->faker->word(), 'quantity' => $this->faker->numberBetween(1, 3)]
                ],
                'total' => $this->faker->randomFloat(2, 10, 100)
            ]),
            'queue_number' => $this->faker->unique()->numberBetween(1, 1000),
            'quantity_purchased' => $this->faker->optional()->numberBetween(1, 10),
            'estimated_wait_time' => $this->faker->numberBetween(5, 30),
            'notes' => $this->faker->optional()->sentence(),
            'order_status' => $this->faker->randomElement(['queued', 'kitchen', 'preparing', 'serving', 'completed', 'cancelled']),
            'cashier_id' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'queued',
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'preparing',
        ]);
    }

    public function serving(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'serving',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'cancelled',
        ]);
    }

    public function withCashier(): static
    {
        return $this->state(fn (array $attributes) => [
            'cashier_id' => Cashier::factory(),
        ]);
    }
} 