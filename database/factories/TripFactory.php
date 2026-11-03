<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    public function definition(): array
    {
        // Somewhere around Tagbilaran City proper.
        $pickupAt = fake()->dateTimeBetween('-14 days', 'now');
        $km = fake()->randomFloat(3, 0.3, 6);
        $passengers = fake()->numberBetween(1, 4);

        return [
            'uuid' => (string) Str::uuid(),
            'driver_id' => Driver::factory(),
            'slot' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
            'passenger_count' => $passengers,
            'pickup_lat' => 9.64 + fake()->randomFloat(5, 0, 0.03),
            'pickup_lng' => 123.85 + fake()->randomFloat(5, 0, 0.04),
            'dropoff_lat' => 9.64 + fake()->randomFloat(5, 0, 0.03),
            'dropoff_lng' => 123.85 + fake()->randomFloat(5, 0, 0.04),
            'pickup_street' => fake()->randomElement(['CPG Avenue', 'Gallares St', 'J.A. Clarin St', 'Carlos P. Garcia East Ave', 'M. Torralba St']),
            'dropoff_street' => fake()->randomElement(['Bohol Circumferential Rd', 'Dao', 'Graham Ave', 'B. Inting St', 'V. Inting Ave']),
            'pickup_at' => $pickupAt,
            'dropoff_at' => (clone $pickupAt)->modify('+'.max(3, (int) round($km * 4)).' minutes'),
            'route_distance_km' => $km,
            'gps_distance_km' => round($km * fake()->randomFloat(2, 0.95, 1.2), 3),
            'fare' => Trip::computeFare($km, $passengers),
            'rounded_down' => false,
        ];
    }
}
