<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardSet>
 */
class CardSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->randomElement(['Panini 2024', 'Topps 2024', 'Upper Deck 2024']),
            'slug' => $this->faker->slug(),
            'brand' => $this->faker->randomElement(['Panini', 'Topps', 'Upper Deck']),
            'year' => $this->faker->year(),
            'season' => $this->faker->randomElement(['2023/24', '2024/25', '2025/26']),
            'description' => $this->faker->sentence(),
            'is_official' => true,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
