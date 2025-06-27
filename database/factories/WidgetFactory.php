<?php

namespace Database\Factories;

use App\Models\Widget;
use App\Models\ScreenLayout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Widget>
 */
class WidgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['time', 'date', 'weather', 'queue', 'announcement', 'custom']);
        
        return [
            'screen_layout_id' => ScreenLayout::factory(),
            'type' => $type,
            'position' => json_encode([
                'x' => $this->faker->numberBetween(0, 8),
                'y' => $this->faker->numberBetween(0, 6),
                'width' => $this->faker->numberBetween(2, 4),
                'height' => $this->faker->numberBetween(1, 3)
            ]),
            'settings' => $this->generateSettingsForType($type),
        ];
    }

    /**
     * Create a widget of specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type,
                'settings' => $this->generateSettingsForType($type),
            ];
        });
    }

    /**
     * Create a time widget.
     */
    public function time(): static
    {
        return $this->ofType('time');
    }

    /**
     * Create a date widget.
     */
    public function date(): static
    {
        return $this->ofType('date');
    }

    /**
     * Create a weather widget.
     */
    public function weather(): static
    {
        return $this->ofType('weather');
    }

    /**
     * Create a queue widget.
     */
    public function queue(): static
    {
        return $this->ofType('queue');
    }

    /**
     * Create an announcement widget.
     */
    public function announcement(): static
    {
        return $this->ofType('announcement');
    }

    /**
     * Create a custom widget.
     */
    public function custom(): static
    {
        return $this->ofType('custom');
    }

    /**
     * Create a widget for a specific layout.
     */
    public function forLayout(ScreenLayout $layout): static
    {
        return $this->state(fn (array $attributes) => [
            'screen_layout_id' => $layout->id,
        ]);
    }

    /**
     * Create a widget with specific position.
     */
    public function atPosition(int $x, int $y, int $width = 2, int $height = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => json_encode([
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height
            ]),
        ]);
    }

    /**
     * Generate appropriate settings for different widget types.
     */
    private function generateSettingsForType(string $type): array
    {
        return match ($type) {
            'time' => [
                'format' => $this->faker->randomElement(['12h', '24h']),
                'timezone' => $this->faker->randomElement(['UTC', 'America/New_York', 'Europe/London']),
                'show_seconds' => $this->faker->boolean(),
                'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
                'color' => $this->faker->hexColor()
            ],
            'date' => [
                'format' => $this->faker->randomElement(['Y-m-d', 'd/m/Y', 'M d, Y', 'l, F j, Y']),
                'timezone' => $this->faker->randomElement(['UTC', 'America/New_York', 'Europe/London']),
                'show_day_name' => $this->faker->boolean(),
                'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
                'color' => $this->faker->hexColor()
            ],
            'weather' => [
                'location' => $this->faker->city(),
                'units' => $this->faker->randomElement(['celsius', 'fahrenheit']),
                'show_forecast' => $this->faker->boolean(),
                'show_humidity' => $this->faker->boolean(),
                'show_wind' => $this->faker->boolean(),
                'refresh_interval' => $this->faker->numberBetween(300, 1800) // 5-30 minutes
            ],
            'queue' => [
                'queue_id' => $this->faker->numberBetween(1, 10),
                'show_current' => $this->faker->boolean(),
                'show_wait_time' => $this->faker->boolean(),
                'show_next_numbers' => $this->faker->numberBetween(1, 5),
                'refresh_interval' => $this->faker->numberBetween(10, 60), // 10-60 seconds
                'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
                'color' => $this->faker->hexColor()
            ],
            'announcement' => [
                'text' => $this->faker->sentence(),
                'scroll_speed' => $this->faker->numberBetween(1, 5),
                'color' => $this->faker->hexColor(),
                'background_color' => $this->faker->hexColor(),
                'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
                'show_border' => $this->faker->boolean(),
                'border_color' => $this->faker->hexColor()
            ],
            'custom' => [
                'content' => $this->faker->paragraph(),
                'refresh_interval' => $this->faker->numberBetween(30, 300), // 30 seconds to 5 minutes
                'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
                'color' => $this->faker->hexColor(),
                'background_color' => $this->faker->hexColor(),
                'alignment' => $this->faker->randomElement(['left', 'center', 'right']),
                'show_border' => $this->faker->boolean()
            ],
            default => []
        };
    }
} 