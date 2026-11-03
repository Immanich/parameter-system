<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->lastName(),
            'last_name' => fake()->lastName(),
            'tricycle_number' => 'TAG-'.fake()->unique()->numerify('####'),
            'contact_number' => '09'.fake()->unique()->numerify('#########'),
            'email' => null,
            'password' => Hash::make('password'),
        ];
    }
}
