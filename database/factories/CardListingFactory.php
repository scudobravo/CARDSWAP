<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CardListing>
 */
class CardListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seller_id' => \App\Models\User::factory(),
            'card_model_id' => \App\Models\CardModel::factory(),
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'condition' => $this->faker->randomElement(['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor']),
            'quantity' => $this->faker->numberBetween(1, 10),
            'language' => $this->faker->randomElement(['italian', 'english', 'spanish', 'french', 'german']),
            'is_foil' => $this->faker->boolean(),
            'is_signed' => $this->faker->boolean(),
            'is_altered' => $this->faker->boolean(),
            'is_first_edition' => $this->faker->boolean(),
            'is_negotiable' => $this->faker->boolean(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['draft', 'active', 'inactive']),
            'images' => $this->faker->randomElements(['image1.jpg', 'image2.jpg', 'image3.jpg'], 2),
        ];
    }
}
