<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'destination' => fake()->city(),
            'departure_date' => fake()->date(),
            'return_date' => fake()->date(),
            'status' => fake()->randomElement(['solicitado', 'aprovado', 'cancelado']),
        ];
    }
}
