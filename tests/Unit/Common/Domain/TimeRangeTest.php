<?php

namespace Tests\Unit\Common\Domain;

use App\Common\Domain\TimeRange;
use CodeIgniter\Test\CIUnitTestCase;

class TimeRangeTest extends CIUnitTestCase
{
    public function testCreateValidTimeRange()
    {
        $timeRange = new TimeRange('08:00', '16:00');

        $this->assertEquals('08:00', $timeRange->getStart());
        $this->assertEquals('16:00', $timeRange->getEnd());
    }

    public function testGetDurationInMinutes()
    {
        $timeRange = new TimeRange('08:00', '16:00');

        // 8 hours = 480 minutes
        $this->assertEquals(480, $timeRange->getDurationInMinutes());

        $timeRange = new TimeRange('09:30', '10:45');

        // 1 hour and 15 minutes = 75 minutes
        $this->assertEquals(75, $timeRange->getDurationInMinutes());
    }

    public function testInvalidTimeFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid time format');

        new TimeRange('8:00', '16:00'); // Missing leading zero
    }

    public function testEndTimeBeforeStartTime()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End time must be after start time');

        new TimeRange('16:00', '08:00');
    }

    public function testSameStartAndEndTime()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End time must be after start time');

        new TimeRange('08:00', '08:00');
    }

    public function testToArray()
    {
        $timeRange = new TimeRange('08:00', '16:00');
        $array = $timeRange->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('start', $array);
        $this->assertArrayHasKey('end', $array);
        $this->assertEquals('08:00', $array['start']);
        $this->assertEquals('16:00', $array['end']);
    }

    public function testFromArray()
    {
        $array = [
            'start' => '08:00',
            'end' => '16:00'
        ];

        $timeRange = TimeRange::fromArray($array);

        $this->assertInstanceOf(TimeRange::class, $timeRange);
        $this->assertEquals('08:00', $timeRange->getStart());
        $this->assertEquals('16:00', $timeRange->getEnd());
    }
}
