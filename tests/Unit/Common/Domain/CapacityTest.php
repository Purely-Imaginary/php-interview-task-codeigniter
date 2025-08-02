<?php

namespace Tests\Unit\Common\Domain;

use App\Common\Domain\Capacity;
use CodeIgniter\Test\CIUnitTestCase;

class CapacityTest extends CIUnitTestCase
{
    public function testCreateValidCapacity()
    {
        $capacity = new Capacity(32);

        $this->assertEquals(32, $capacity->getSeats());
    }

    public function testInvalidNegativeCapacity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be a positive integer');

        new Capacity(-10);
    }

    public function testInvalidZeroCapacity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be a positive integer');

        new Capacity(0);
    }

    public function testInvalidNonNumericCapacity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be a positive integer');

        new Capacity('not a number');
    }

    public function testInvalidFloatCapacity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be a positive integer');

        new Capacity(10.5);
    }

    public function testToArray()
    {
        $capacity = new Capacity(32);
        $array = $capacity->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('seats', $array);
        $this->assertEquals(32, $array['seats']);
    }

    public function testFromArray()
    {
        $array = [
            'seats' => 32
        ];

        $capacity = Capacity::fromArray($array);

        $this->assertInstanceOf(Capacity::class, $capacity);
        $this->assertEquals(32, $capacity->getSeats());
    }
}
