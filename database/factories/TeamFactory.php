<?php

namespace Database\Factories;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'league_id' => League::factory(),
            'name' => $this->faker->randomElement(['Inter', 'Milan', 'Juventus', 'Roma', 'Napoli']),
            'slug' => $this->faker->slug(),
            'city' => $this->faker->city(),
            'stadium' => $this->faker->randomElement(['San Siro', 'Stadio Olimpico', 'Allianz Stadium', 'Stadio Diego Armando Maradona']),
            'logo_url' => $this->faker->imageUrl(),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
