<?php

namespace App\Models;

use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'slot',
        'passenger_count',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'pickup_street',
        'dropoff_street',
        'pickup_at',
        'dropoff_at',
        'route_distance_km',
        'gps_distance_km',
        'fare',
        'rounded_down',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_at' => 'datetime',
            'dropoff_at' => 'datetime',
            'passenger_count' => 'integer',
            'route_distance_km' => 'float',
            'gps_distance_km' => 'float',
            'fare' => 'float',
            'rounded_down' => 'boolean',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Fare per spec: per passenger max(15, 15 * km), times passengers, 1 decimal.
     */
    public static function computeFare(float $routeKm, int $passengers, bool $roundDown = false): float
    {
        $fare = round(max(15, 15 * $routeKm) * $passengers, 1);

        return $roundDown ? floor($fare) : $fare;
    }
}
