<?php

namespace Tests\Unit\Common\Domain;

use App\Common\Domain\Distance;
use CodeIgniter\Test\CIUnitTestCase;

class DistanceTest extends CIUnitTestCase
{
    public function testCreateValidDistance()
    {
        $distance = new Distance(1800);

        $this->assertEquals(1800, $distance->getMeters());
    }

    public function testGetKilometers()
    {
        $distance = new Distance(1800);

        // 1800 meters = 1.8 kilometers
        $this->assertEquals(1.8, $distance->getKilometers());

        $distance = new Distance(500);

        // 500 meters = 0.5 kilometers
        $this->assertEquals(0.5, $distance->getKilometers());
    }

    public function testInvalidNegativeDistance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be a positive number');

        new Distance(-100);
    }

    public function testInvalidZeroDistance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be a positive number');

        new Distance(0);
    }

    public function testInvalidNonNumericDistance()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be a positive number');

        new Distance('not a number');
    }

    public function testToArray()
    {
        $distance = new Distance(1800);
        $array = $distance->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('meters', $array);
        $this->assertEquals(1800, $array['meters']);
    }

    public function testFromArray()
    {
        $array = [
            'meters' => 1800
        ];

        $distance = Distance::fromArray($array);

        $this->assertInstanceOf(Distance::class, $distance);
        $this->assertEquals(1800, $distance->getMeters());
    }
}
