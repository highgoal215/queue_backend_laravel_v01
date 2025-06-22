<?php

namespace Database\Factories;

use App\Models\ScreenLayout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScreenLayout>
 */
class ScreenLayoutFactory extends Factory
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
            'device_id' => 'device_' . $this->faker->numberBetween(1, 100),
            'layout_config' => [
                'grid' => [
                    'columns' => $this->faker->numberBetween(8, 12),
                    'rows' => $this->faker->numberBetween(6, 10)
                ],
                'widgets' => []
            ],
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the layout is default for its device.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Create a layout with specific device ID.
     */
    public function forDevice(string $deviceId): static
    {
        return $this->state(fn (array $attributes) => [
            'device_id' => $deviceId,
        ]);
    }

    /**
     * Create a layout with widgets.
     */
    public function withWidgets(int $count = 3): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $widgets = [];
            $widgetTypes = ['time', 'date', 'weather', 'queue', 'announcement', 'custom'];
            
            for ($i = 0; $i < $count; $i++) {
                $widgets[] = [
                    'type' => $this->faker->randomElement($widgetTypes),
                    'position' => [
                        'x' => $this->faker->numberBetween(0, 8),
                        'y' => $this->faker->numberBetween(0, 6),
                        'width' => $this->faker->numberBetween(2, 4),
                        'height' => $this->faker->numberBetween(1, 3)
                    ],
                    'settings' => $this->generateWidgetSettings($this->faker->randomElement($widgetTypes))
                ];
            }
            
            return [
                'layout_config' => [
                    'grid' => [
                        'columns' => 12,
                        'rows' => 8
                    ],
                    'widgets' => $widgets
                ]
            ];
        });
    }

    /**
     * Generate appropriate settings for different widget types.
     */
    private function generateWidgetSettings(string $type): array
    {
        return match ($type) {
            'time' => [
                'format' => $this->faker->randomElement(['12h', '24h']),
                'timezone' => $this->faker->randomElement(['UTC', 'America/New_York', 'Europe/London']),
                'show_seconds' => $this->faker->boolean()
            ],
            'date' => [
                'format' => $this->faker->randomElement(['Y-m-d', 'd/m/Y', 'M d, Y']),
                'timezone' => $this->faker->randomElement(['UTC', 'America/New_York', 'Europe/London'])
            ],
            'weather' => [
                'location' => $this->faker->city(),
                'units' => $this->faker->randomElement(['celsius', 'fahrenheit']),
                'show_forecast' => $this->faker->boolean()
            ],
            'queue' => [
                'queue_id' => $this->faker->numberBetween(1, 10),
                'show_current' => $this->faker->boolean(),
                'show_wait_time' => $this->faker->boolean()
            ],
            'announcement' => [
                'text' => $this->faker->sentence(),
                'scroll_speed' => $this->faker->numberBetween(1, 5),
                'color' => $this->faker->hexColor()
            ],
            'custom' => [
                'content' => $this->faker->paragraph(),
                'refresh_interval' => $this->faker->numberBetween(30, 300)
            ],
            default => []
        };
    }
} 