<?php

namespace Tests\Unit\FleetManagement\Domain;

use App\FleetManagement\Domain\Wagon;
use App\Common\Domain\Capacity;
use App\Common\Domain\Speed;
use CodeIgniter\Test\CIUnitTestCase;

class WagonTest extends CIUnitTestCase
{
    public function testCreateWagon()
    {
        $id = 'wagon_123';
        $seatCount = new Capacity(32);
        $speed = new Speed(1.2);

        $wagon = new Wagon($id, $seatCount, $speed);

        $this->assertEquals($id, $wagon->getId());
        $this->assertSame($seatCount, $wagon->getSeatCount());
        $this->assertSame($speed, $wagon->getSpeed());
    }

    public function testCreateWagonWithFactory()
    {
        $wagon = Wagon::create(32, 1.2);

        $this->assertStringStartsWith('wagon_', $wagon->getId());
        $this->assertEquals(32, $wagon->getSeatCount()->getSeats());
        $this->assertEquals(1.2, $wagon->getSpeed()->getMetersPerSecond());
    }

    public function testToArray()
    {
        $id = 'wagon_123';
        $seatCount = new Capacity(32);
        $speed = new Speed(1.2);

        $wagon = new Wagon($id, $seatCount, $speed);
        $array = $wagon->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('seat_count', $array);
        $this->assertArrayHasKey('speed', $array);

        $this->assertEquals($id, $array['id']);
        $this->assertEquals(['seats' => 32], $array['seat_count']);
        $this->assertEquals(['meters_per_second' => 1.2], $array['speed']);
    }

    public function testFromArray()
    {
        $array = [
            'id' => 'wagon_123',
            'seat_count' => ['seats' => 32],
            'speed' => ['meters_per_second' => 1.2]
        ];

        $wagon = Wagon::fromArray($array);

        $this->assertInstanceOf(Wagon::class, $wagon);
        $this->assertEquals('wagon_123', $wagon->getId());
        $this->assertEquals(32, $wagon->getSeatCount()->getSeats());
        $this->assertEquals(1.2, $wagon->getSpeed()->getMetersPerSecond());
    }
}
