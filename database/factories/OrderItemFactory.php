<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'card_listing_id' => \App\Models\CardListing::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => $this->faker->randomFloat(2, 5, 500),
            'total_price' => $this->faker->randomFloat(2, 5, 500),
            'condition' => $this->faker->randomElement(['Mint', 'Near Mint', 'Excellent', 'Good', 'Poor']),
            'notes' => $this->faker->optional()->sentence()
        ];
    }
}
