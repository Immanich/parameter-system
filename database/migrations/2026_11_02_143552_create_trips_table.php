<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->char('slot', 1);
            $table->unsignedTinyInteger('passenger_count')->default(1);
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->decimal('dropoff_lat', 10, 7);
            $table->decimal('dropoff_lng', 10, 7);
            $table->string('pickup_street')->nullable();
            $table->string('dropoff_street')->nullable();
            $table->dateTime('pickup_at');
            $table->dateTime('dropoff_at');
            $table->decimal('route_distance_km', 8, 3);
            $table->decimal('gps_distance_km', 8, 3)->nullable();
            $table->decimal('fare', 8, 2);
            $table->boolean('rounded_down')->default(false);
            $table->timestamps();

            $table->index(['driver_id', 'pickup_at']);
            $table->index('pickup_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
