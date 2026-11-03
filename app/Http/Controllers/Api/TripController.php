<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    /**
     * Batch upload. Idempotent on the client-generated uuid; each trip is
     * validated on its own so one bad trip does not block the rest.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'trips' => ['required', 'array', 'min:1', 'max:100'],
        ]);

        /** @var Driver $driver */
        $driver = $request->user();
        $accepted = [];
        $rejected = [];

        foreach ($request->input('trips') as $item) {
            $uuid = is_array($item) ? ($item['uuid'] ?? null) : null;
            $validator = Validator::make(is_array($item) ? $item : [], self::rules());

            if ($validator->fails()) {
                $rejected[] = ['uuid' => $uuid, 'errors' => $validator->errors()];

                continue;
            }

            $data = $validator->validated();
            // Phones send ISO-8601 with offset; store in app timezone (UTC).
            foreach (['pickup_at', 'dropoff_at'] as $key) {
                $data[$key] = Carbon::parse($data[$key])->setTimezone(config('app.timezone'));
            }
            $existing = Trip::where('uuid', $data['uuid'])->first();

            if ($existing) {
                if ($existing->driver_id === $driver->id) {
                    $accepted[] = $data['uuid'];
                } else {
                    $rejected[] = ['uuid' => $data['uuid'], 'errors' => ['uuid' => ['UUID already used.']]];
                }

                continue;
            }

            $driver->trips()->create($data);
            $accepted[] = $data['uuid'];
        }

        return response()->json(['accepted' => $accepted, 'rejected' => $rejected]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'slot' => ['required', 'string', 'in:A,B,C,D,E'],
            'passenger_count' => ['required', 'integer', 'between:1,5'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            'pickup_street' => ['nullable', 'string', 'max:255'],
            'dropoff_street' => ['nullable', 'string', 'max:255'],
            'pickup_at' => ['required', 'date'],
            'dropoff_at' => ['required', 'date', 'after_or_equal:pickup_at'],
            'route_distance_km' => ['required', 'numeric', 'min:0', 'max:1000'],
            'gps_distance_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fare' => ['required', 'numeric', 'min:0', 'max:100000'],
            'rounded_down' => ['sometimes', 'boolean'],
        ];
    }
}
