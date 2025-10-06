<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAddress>
 */
class UserAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => $this->faker->randomElement(['Casa', 'Ufficio', 'Indirizzo principale', 'Indirizzo secondario']),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'company' => $this->faker->optional(0.3)->company(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.2)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state_province' => $this->faker->optional(0.8)->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->randomElement(['IT', 'FR', 'DE', 'ES', 'CH']),
            'phone' => $this->faker->optional(0.7)->phoneNumber(),
            'is_default' => false,
            'is_billing' => $this->faker->boolean(30),
            'is_shipping' => $this->faker->boolean(80),
        ];
    }

    /**
     * Indicate that the address is the default one.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the address is for billing.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_billing' => true,
        ]);
    }

    /**
     * Indicate that the address is for shipping.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shipping' => true,
        ]);
    }
}
