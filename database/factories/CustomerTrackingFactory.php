<?php

namespace Database\Factories;

use App\Models\CustomerTracking;
use App\Models\QueueEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerTracking>
 */
class CustomerTrackingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'queue_entry_id' => QueueEntry::factory(),
            'qr_code_url' => $this->faker->url(),
            'status' => $this->faker->randomElement(['waiting', 'called', 'served', 'cancelled', 'no_show']),
            'estimated_wait_time' => $this->faker->numberBetween(5, 60),
            'current_position' => $this->faker->numberBetween(0, 20),
            'last_updated' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Create tracking with specific status.
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Create tracking for waiting customers.
     */
    public function waiting(): static
    {
        return $this->withStatus('waiting');
    }

    /**
     * Create tracking for called customers.
     */
    public function called(): static
    {
        return $this->withStatus('called');
    }

    /**
     * Create tracking for served customers.
     */
    public function served(): static
    {
        return $this->withStatus('served');
    }

    /**
     * Create tracking for cancelled customers.
     */
    public function cancelled(): static
    {
        return $this->withStatus('cancelled');
    }

    /**
     * Create tracking for no-show customers.
     */
    public function noShow(): static
    {
        return $this->withStatus('no_show');
    }

    /**
     * Create tracking for a specific queue entry.
     */
    public function forEntry(QueueEntry $entry): static
    {
        return $this->state(fn (array $attributes) => [
            'queue_entry_id' => $entry->id,
        ]);
    }

    /**
     * Create tracking with specific wait time.
     */
    public function withWaitTime(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_wait_time' => $minutes,
        ]);
    }

    /**
     * Create tracking with specific position.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'current_position' => $position,
        ]);
    }

    /**
     * Create tracking for immediate service (no wait).
     */
    public function immediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_wait_time' => 0,
            'current_position' => 0,
            'status' => 'called',
        ]);
    }

    /**
     * Create tracking for long wait times.
     */
    public function longWait(): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_wait_time' => $this->faker->numberBetween(30, 120),
            'current_position' => $this->faker->numberBetween(5, 50),
            'status' => 'waiting',
        ]);
    }

    /**
     * Create tracking for short wait times.
     */
    public function shortWait(): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_wait_time' => $this->faker->numberBetween(1, 15),
            'current_position' => $this->faker->numberBetween(1, 5),
            'status' => 'waiting',
        ]);
    }

    /**
     * Create tracking that was recently updated.
     */
    public function recentlyUpdated(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_updated' => $this->faker->dateTimeBetween('-5 minutes', 'now'),
        ]);
    }

    /**
     * Create tracking that hasn't been updated recently.
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_updated' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
        ]);
    }

    /**
     * Create tracking without a queue entry (standalone tracking).
     */
    public function standalone(): static
    {
        return $this->state(fn (array $attributes) => [
            'queue_entry_id' => QueueEntry::factory(),
        ]);
    }
} 