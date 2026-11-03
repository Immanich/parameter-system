<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LedgerController extends Controller
{
    /** Display timezone for the single supported city. */
    private const TZ = 'Asia/Manila';

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'tricycle' => ['nullable', 'string', 'max:50'],
        ]);

        $query = Trip::query()
            ->with('driver:id,tricycle_number,first_name,last_name')
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('pickup_at', '>=', Carbon::parse($d, self::TZ)->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('pickup_at', '<=', Carbon::parse($d, self::TZ)->endOfDay()->utc()))
            ->when($filters['tricycle'] ?? null, fn ($q, $t) => $q->whereHas('driver', fn ($d) => $d->where('tricycle_number', 'like', "%{$t}%")));

        $totals = (clone $query)->toBase()
            ->selectRaw('count(*) as trips, coalesce(sum(fare), 0) as fare, coalesce(sum(route_distance_km), 0) as km, coalesce(sum(passenger_count), 0) as passengers')
            ->first();

        $trips = $query->latest('pickup_at')->paginate(50)->withQueryString()
            ->through(function (Trip $trip) {
                $pickup = $trip->pickup_at->timezone(self::TZ);

                return [
                    'id' => $trip->id,
                    'tricycle_number' => $trip->driver->tricycle_number,
                    'driver_name' => trim($trip->driver->first_name.' '.$trip->driver->last_name),
                    'date' => $pickup->format('Y-m-d'),
                    'pickup_time' => $pickup->format('g:i A'),
                    'dropoff_time' => $trip->dropoff_at->timezone(self::TZ)->format('g:i A'),
                    'pickup_location' => $trip->pickup_street ?? sprintf('%.5f, %.5f', $trip->pickup_lat, $trip->pickup_lng),
                    'dropoff_location' => $trip->dropoff_street ?? sprintf('%.5f, %.5f', $trip->dropoff_lat, $trip->dropoff_lng),
                    'pickup_coords' => [(float) $trip->pickup_lat, (float) $trip->pickup_lng],
                    'dropoff_coords' => [(float) $trip->dropoff_lat, (float) $trip->dropoff_lng],
                    'distance_km' => $trip->route_distance_km,
                    'gps_distance_km' => $trip->gps_distance_km,
                    'passenger_count' => $trip->passenger_count,
                    'fare' => $trip->fare,
                    'rounded_down' => $trip->rounded_down,
                    'slot' => $trip->slot,
                ];
            });

        return Inertia::render('ledger', [
            'trips' => $trips,
            'filters' => [
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'tricycle' => $filters['tricycle'] ?? '',
            ],
            'totals' => [
                'trips' => (int) $totals->trips,
                'fare' => round((float) $totals->fare, 2),
                'km' => round((float) $totals->km, 2),
                'passengers' => (int) $totals->passengers,
            ],
        ]);
    }
}
