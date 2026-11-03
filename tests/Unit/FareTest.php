<?php

namespace Tests\Unit;

use App\Models\Trip;
use PHPUnit\Framework\TestCase;

class FareTest extends TestCase
{
    public function test_fare_rules()
    {
        $this->assertSame(15.0, Trip::computeFare(0.4, 1));   // minimum
        $this->assertSame(30.0, Trip::computeFare(0.4, 2));
        $this->assertSame(37.5, Trip::computeFare(2.5, 1));
        $this->assertSame(55.5, Trip::computeFare(1.234, 3)); // 18.51 * 3 = 55.53
        $this->assertSame(37.0, Trip::computeFare(2.5, 1, true));
    }
}
