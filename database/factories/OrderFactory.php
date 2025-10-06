<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . $this->faker->unique()->numberBetween(1000, 9999),
            'buyer_id' => \App\Models\User::factory(),
            'seller_id' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['pending', 'paid', 'shipped', 'delivered', 'cancelled']),
            'subtotal' => $this->faker->randomFloat(2, 10, 1000),
            'shipping_cost' => $this->faker->randomFloat(2, 5, 50),
            'tax_amount' => $this->faker->randomFloat(2, 2, 100),
            'total_amount' => $this->faker->randomFloat(2, 20, 1200),
            'shipping_address' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'address_line_1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'country' => 'IT',
                'postal_code' => $this->faker->postcode()
            ],
            'billing_address' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'address_line_1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'country' => 'IT',
                'postal_code' => $this->faker->postcode()
            ],
            'notes' => $this->faker->optional()->sentence(),
            'tracking_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{9}[A-Z]{2}'),
            'shipped_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'delivered_at' => $this->faker->optional()->dateTimeBetween('-2 weeks', 'now')
        ];
    }
}
