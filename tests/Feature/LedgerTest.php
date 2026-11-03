<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/ledger')->assertRedirect('/login');
    }

    public function test_registration_is_disabled()
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_admin_sees_trips_in_the_ledger()
    {
        Trip::factory()->count(3)->create();
        $this->actingAs(User::factory()->create());

        $this->get('/ledger')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ledger')
                ->has('trips.data', 3)
                ->where('totals.trips', 3));
    }

    public function test_ledger_filters_by_tricycle_number()
    {
        $trip = Trip::factory()->create();
        Trip::factory()->count(2)->create();
        $this->actingAs(User::factory()->create());

        $this->get('/ledger?tricycle='.$trip->driver->tricycle_number)
            ->assertInertia(fn (Assert $page) => $page->has('trips.data', 1));
    }
}
