<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Italia', 'Europa', 'Mondo']),
            'country_code' => $this->faker->randomElement(['IT', 'DE', 'FR', 'ES', 'GB']),
            'region' => $this->faker->state(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'shipping_cost' => $this->faker->randomFloat(2, 3, 15),
            'base_cost' => $this->faker->randomFloat(2, 2, 10),
            'cost_per_kg' => $this->faker->randomFloat(2, 0.5, 2),
            'cost_per_euro' => $this->faker->randomFloat(2, 0.01, 0.05),
            'free_shipping_threshold' => $this->faker->randomFloat(2, 50, 200),
            'max_weight_kg' => $this->faker->randomFloat(2, 1, 5),
            'max_value_euro' => $this->faker->randomFloat(2, 1000, 5000),
            'requires_seller_approval' => false,
            'allowed_seller_roles' => ['seller', 'admin'],
            'min_seller_rating' => 0,
            'min_seller_sales' => 0,
            'delivery_days_min' => $this->faker->numberBetween(1, 3),
            'delivery_days_max' => $this->faker->numberBetween(5, 10),
            'is_active' => true,
        ];
    }
}
