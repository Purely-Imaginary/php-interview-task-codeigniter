<?php

namespace Tests\Unit\Common\Domain;

use App\Common\Domain\Speed;
use App\Common\Domain\Distance;
use CodeIgniter\Test\CIUnitTestCase;

class SpeedTest extends CIUnitTestCase
{
    public function testCreateValidSpeed()
    {
        $speed = new Speed(1.2);

        $this->assertEquals(1.2, $speed->getMetersPerSecond());
    }

    public function testGetKilometersPerHour()
    {
        $speed = new Speed(1.2);

        // 1.2 m/s = 4.32 km/h
        $this->assertEquals(4.32, $speed->getKilometersPerHour());

        $speed = new Speed(5);

        // 5 m/s = 18 km/h
        $this->assertEquals(18, $speed->getKilometersPerHour());
    }

    public function testCalculateTimeToTravel()
    {
        $speed = new Speed(2);
        $distance = new Distance(100);

        // 100 meters at 2 m/s = 50 seconds
        $this->assertEquals(50, $speed->calculateTimeToTravel($distance));

        // Test with non-integer result (should be rounded up)
        $speed = new Speed(3);
        $distance = new Distance(100);

        // 100 meters at 3 m/s = 33.33... seconds, should be rounded up to 34
        $this->assertEquals(34, $speed->calculateTimeToTravel($distance));
    }

    public function testInvalidNegativeSpeed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Speed must be a positive number');

        new Speed(-1.5);
    }

    public function testInvalidZeroSpeed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Speed must be a positive number');

        new Speed(0);
    }

    public function testInvalidNonNumericSpeed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Speed must be a positive number');

        new Speed('not a number');
    }

    public function testToArray()
    {
        $speed = new Speed(1.2);
        $array = $speed->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('meters_per_second', $array);
        $this->assertEquals(1.2, $array['meters_per_second']);
    }

    public function testFromArray()
    {
        $array = [
            'meters_per_second' => 1.2
        ];

        $speed = Speed::fromArray($array);

        $this->assertInstanceOf(Speed::class, $speed);
        $this->assertEquals(1.2, $speed->getMetersPerSecond());
    }
}
