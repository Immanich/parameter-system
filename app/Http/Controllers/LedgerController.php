<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Services\NeonRealtime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LedgerController extends Controller
{
    /** Display timezone for the single supported city. */
    private const TZ = 'Asia/Manila';

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $query = $this->query($filters);

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

    /**
     * Export the filtered ledger as CSV to Neon Object Storage and redirect to a presigned download URL.
     */
    public function export(Request $request): RedirectResponse
    {
        $filters = $this->filters($request);

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['date', 'pickup_time', 'dropoff_time', 'tricycle_number', 'driver', 'slot', 'passengers', 'pickup', 'dropoff', 'route_km', 'gps_km', 'fare', 'rounded_down']);
        $this->query($filters)->oldest('pickup_at')->chunk(500, function ($trips) use ($csv) {
            foreach ($trips as $trip) {
                $pickup = $trip->pickup_at->timezone(self::TZ);
                fputcsv($csv, [
                    $pickup->format('Y-m-d'),
                    $pickup->format('H:i'),
                    $trip->dropoff_at->timezone(self::TZ)->format('H:i'),
                    $trip->driver->tricycle_number,
                    trim($trip->driver->first_name.' '.$trip->driver->last_name),
                    $trip->slot,
                    $trip->passenger_count,
                    $trip->pickup_street ?? "{$trip->pickup_lat},{$trip->pickup_lng}",
                    $trip->dropoff_street ?? "{$trip->dropoff_lat},{$trip->dropoff_lng}",
                    $trip->route_distance_km,
                    $trip->gps_distance_km,
                    $trip->fare,
                    $trip->rounded_down ? 1 : 0,
                ]);
            }
        });
        rewind($csv);

        $name = 'ledger-'.now(self::TZ)->format('Ymd-His').'.csv';
        $key = 'exports/'.Str::uuid().'/'.$name;
        Storage::disk('s3')->writeStream($key, $csv, ['ContentType' => 'text/csv']);
        fclose($csv);

        return redirect()->away(Storage::disk('s3')->temporaryUrl($key, now()->addMinutes(10), [
            'ResponseContentDisposition' => "attachment; filename=\"{$name}\"",
        ]));
    }

    /**
     * Seal a Neon Realtime live query that changes whenever a trip is added or updated,
     * so the open ledger can reload itself.
     */
    public function live(NeonRealtime $realtime): JsonResponse
    {
        return response()->json($realtime->seal(
            'SELECT id, updated_at FROM trips ORDER BY updated_at DESC, id DESC LIMIT 1',
        ));
    }

    /**
     * @return array{from?: string|null, to?: string|null, tricycle?: string|null}
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'tricycle' => ['nullable', 'string', 'max:50'],
        ]);
    }

    /**
     * @param  array{from?: string|null, to?: string|null, tricycle?: string|null}  $filters
     * @return Builder<Trip>
     */
    private function query(array $filters): Builder
    {
        return Trip::query()
            ->with('driver:id,tricycle_number,first_name,last_name')
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('pickup_at', '>=', Carbon::parse($d, self::TZ)->startOfDay()->utc()))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('pickup_at', '<=', Carbon::parse($d, self::TZ)->endOfDay()->utc()))
            ->when($filters['tricycle'] ?? null, fn ($q, $t) => $q->whereHas('driver', fn ($d) => $d->where('tricycle_number', 'like', "%{$t}%")));
    }
}
