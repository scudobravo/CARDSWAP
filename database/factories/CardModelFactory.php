<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CardSet;
use App\Models\Player;
use App\Models\Team;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardModel>
 */
class CardModelFactory extends Factory
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
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->sentence(),
            'set_name' => $this->faker->randomElement(['Panini 2024', 'Topps 2024', 'Upper Deck 2024']),
            'year' => $this->faker->year(),
            'rarity' => $this->faker->randomElement(['common', 'uncommon', 'rare', 'mythic', 'special']),
            'card_number' => $this->faker->numberBetween(1, 500),
            'artist' => $this->faker->name(),
            'image_url' => $this->faker->imageUrl(),
            'attributes' => $this->faker->randomElements(['foil', 'signed', 'altered'], 2),
            'is_active' => true,
        ];
    }
}
