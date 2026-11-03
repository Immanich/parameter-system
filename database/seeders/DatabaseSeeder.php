<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@parameter.test')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ],
        );

        // Demo data for the ledger outside production.
        if (! app()->isProduction() && Trip::count() === 0) {
            Driver::factory(3)
                ->has(Trip::factory()->count(15))
                ->create();
        }
    }
}
