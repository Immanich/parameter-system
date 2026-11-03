<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_register_without_email()
    {
        $this->postJson('/api/drivers/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'tricycle_number' => 'TAG-0001',
            'contact_number' => '09171234567',
            'password' => 'secret123',
        ])->assertCreated()->assertJsonStructure(['token', 'driver' => ['id', 'tricycle_number']]);
    }

    public function test_driver_can_login_with_tricycle_or_contact_number()
    {
        $driver = Driver::factory()->create();

        $this->postJson('/api/drivers/login', ['login' => $driver->tricycle_number, 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/drivers/login', ['login' => $driver->contact_number, 'password' => 'password'])
            ->assertOk();
        $this->postJson('/api/drivers/login', ['login' => $driver->contact_number, 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_trip_upload_is_idempotent_and_validates_each_trip()
    {
        $driver = Driver::factory()->create();
        Sanctum::actingAs($driver);

        $good = $this->trip();
        $bad = ['uuid' => (string) Str::uuid(), 'slot' => 'Z'];

        $this->postJson('/api/trips', ['trips' => [$good, $bad]])
            ->assertOk()
            ->assertJsonPath('accepted', [$good['uuid']])
            ->assertJsonPath('rejected.0.uuid', $bad['uuid']);

        $this->postJson('/api/trips', ['trips' => [$good]])
            ->assertOk()->assertJsonPath('accepted', [$good['uuid']]);

        $this->assertDatabaseCount('trips', 1);
        $this->assertDatabaseHas('trips', ['uuid' => $good['uuid'], 'pickup_at' => '2026-11-02 00:30:00']);
    }

    public function test_uuid_owned_by_another_driver_is_rejected()
    {
        $trip = $this->trip();
        Sanctum::actingAs(Driver::factory()->create());
        $this->postJson('/api/trips', ['trips' => [$trip]]);

        Sanctum::actingAs(Driver::factory()->create());
        $this->postJson('/api/trips', ['trips' => [$trip]])
            ->assertJsonPath('accepted', [])
            ->assertJsonPath('rejected.0.uuid', $trip['uuid']);
    }

    public function test_admin_users_cannot_use_driver_endpoints()
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/trips', ['trips' => [$this->trip()]])->assertForbidden();
    }

    private function trip(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'slot' => 'A',
            'passenger_count' => 2,
            'pickup_lat' => 9.6475,
            'pickup_lng' => 123.8556,
            'dropoff_lat' => 9.6560,
            'dropoff_lng' => 123.8700,
            'pickup_street' => 'CPG Avenue',
            'dropoff_street' => 'Dao',
            'pickup_at' => '2026-11-02T08:30:00+08:00',
            'dropoff_at' => '2026-11-02T08:42:00+08:00',
            'route_distance_km' => 2.4,
            'gps_distance_km' => 2.6,
            'fare' => 72.0,
            'rounded_down' => false,
        ];
    }
}
