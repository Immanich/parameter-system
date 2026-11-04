<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Local dev talks to a remote Neon database whose first connection after idle
        // (scale-to-zero wake-up) can take ~25s; don't let `php artisan serve` kill it at 30s.
        if ($this->app->isLocal() && ! $this->app->runningInConsole()) {
            set_time_limit(120);
        }
    }
}
