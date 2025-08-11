<?php

namespace Tests\Unit\FleetManagement\Domain;

use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\Wagon;
use App\Common\Domain\Distance;
use App\Common\Domain\TimeRange;
use App\Common\Domain\CoasterConfigurationChanged;
use CodeIgniter\Test\CIUnitTestCase;

class CoasterTest extends CIUnitTestCase
{
    private $id = 'coaster_123';
    private $personnelCount = 16;
    private $dailyClients = 60000;
    private $trackLength;
    private $operatingHours;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackLength = new Distance(1800);
        $this->operatingHours = new TimeRange('08:00', '16:00');
    }

    public function testCreateCoaster()
    {
        $coaster = new Coaster(
            $this->id,
            $this->personnelCount,
            $this->dailyClients,
            $this->trackLength,
            $this->operatingHours
        );

        $this->assertEquals($this->id, $coaster->getId());
        $this->assertEquals($this->personnelCount, $coaster->getPersonnelCount());
        $this->assertEquals($this->dailyClients, $coaster->getDailyClients());
        $this->assertSame($this->trackLength, $coaster->getTrackLength());
        $this->assertSame($this->operatingHours, $coaster->getOperatingHours());
        $this->assertEmpty($coaster->getWagons());
    }

    public function testCreateCoasterWithFactory()
    {
        $coaster = Coaster::create(
            $this->personnelCount,
            $this->dailyClients,
            1800,
            '08:00',
            '16:00'
        );

        $this->assertStringStartsWith('coaster_', $coaster->getId());
        $this->assertEquals($this->personnelCount, $coaster->getPersonnelCount());
        $this->assertEquals($this->dailyClients, $coaster->getDailyClients());
        $this->assertEquals(1800, $coaster->getTrackLength()->getMeters());
        $this->assertEquals('08:00', $coaster->getOperatingHours()->getStart());
        $this->assertEquals('16:00', $coaster->getOperatingHours()->getEnd());

        // Check that a domain event was recorded
        $events = $coaster->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CoasterConfigurationChanged::class, $events[0]);
        $this->assertEquals($coaster->getId(), $events[0]->getCoasterId());
    }

    public function testUpdateCoaster()
    {
        $coaster = new Coaster(
            $this->id,
            $this->personnelCount,
            $this->dailyClients,
            $this->trackLength,
            $this->operatingHours
        );

        $newPersonnelCount = 18;
        $newDailyClients = 65000;
        $newOperatingHours = new TimeRange('08:00', '17:00');

        $coaster->update($newPersonnelCount, $newDailyClients, $newOperatingHours);

        $this->assertEquals($newPersonnelCount, $coaster->getPersonnelCount());
        $this->assertEquals($newDailyClients, $coaster->getDailyClients());
        $this->assertSame($newOperatingHours, $coaster->getOperatingHours());

        // Check that a domain event was recorded
        $events = $coaster->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CoasterConfigurationChanged::class, $events[0]);
    }

    public function testAddWagon()
    {
        $coaster = new Coaster(
            $this->id,
            $this->personnelCount,
            $this->dailyClients,
            $this->trackLength,
            $this->operatingHours
        );

        $wagon = Wagon::create(32, 1.2);
        $coaster->addWagon($wagon);

        $wagons = $coaster->getWagons();
        $this->assertCount(1, $wagons);
        $this->assertArrayHasKey($wagon->getId(), $wagons);
        $this->assertSame($wagon, $wagons[$wagon->getId()]);

        // Check that a domain event was recorded
        $events = $coaster->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CoasterConfigurationChanged::class, $events[0]);
    }

    public function testRemoveWagon()
    {
        $coaster = new Coaster(
            $this->id,
            $this->personnelCount,
            $this->dailyClients,
            $this->trackLength,
            $this->operatingHours
        );

        $wagon = Wagon::create(32, 1.2);
        $coaster->addWagon($wagon);

        // Clear events from adding the wagon
        $coaster->releaseEvents();

        $coaster->removeWagon($wagon->getId());

        $this->assertEmpty($coaster->getWagons());

        // Check that a domain event was recorded
        $events = $coaster->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(CoasterConfigurationChanged::class, $events[0]);
    }

    public function testRemoveNonExistentWagon()
    {
        $coaster = new Coaster(
            $this->id,
            $this->personnelCount,
            $this->dailyClients,
            $this->trackLength,
            $this->operatingHours
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wagon with ID non_existent_wagon not found');

        $coaster->removeWagon('non_existent_wagon');
    }

    public function testToArray()
    {
        $coaster = new Coaster(
            $this->id,
            $this->personnelCount,
            $this->dailyClients,
            $this->trackLength,
            $this->operatingHours
        );

        $wagon = Wagon::create(32, 1.2);
        $coaster->addWagon($wagon);

        $array = $coaster->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('personnel_count', $array);
        $this->assertArrayHasKey('daily_clients', $array);
        $this->assertArrayHasKey('track_length', $array);
        $this->assertArrayHasKey('operating_hours', $array);
        $this->assertArrayHasKey('wagons', $array);

        $this->assertEquals($this->id, $array['id']);
        $this->assertEquals($this->personnelCount, $array['personnel_count']);
        $this->assertEquals($this->dailyClients, $array['daily_clients']);
        $this->assertEquals(['meters' => 1800], $array['track_length']);
        $this->assertEquals(['start' => '08:00', 'end' => '16:00'], $array['operating_hours']);
        $this->assertArrayHasKey($wagon->getId(), $array['wagons']);
    }

    public function testFromArray()
    {
        $wagonId = 'wagon_123';
        $array = [
            'id' => $this->id,
            'personnel_count' => $this->personnelCount,
            'daily_clients' => $this->dailyClients,
            'track_length' => ['meters' => 1800],
            'operating_hours' => ['start' => '08:00', 'end' => '16:00'],
            'wagons' => [
                $wagonId => [
                    'id' => $wagonId,
                    'seat_count' => ['seats' => 32],
                    'speed' => ['meters_per_second' => 1.2]
                ]
            ]
        ];

        $coaster = Coaster::fromArray($array);

        $this->assertInstanceOf(Coaster::class, $coaster);
        $this->assertEquals($this->id, $coaster->getId());
        $this->assertEquals($this->personnelCount, $coaster->getPersonnelCount());
        $this->assertEquals($this->dailyClients, $coaster->getDailyClients());
        $this->assertEquals(1800, $coaster->getTrackLength()->getMeters());
        $this->assertEquals('08:00', $coaster->getOperatingHours()->getStart());
        $this->assertEquals('16:00', $coaster->getOperatingHours()->getEnd());

        $wagons = $coaster->getWagons();
        $this->assertCount(1, $wagons);
        $this->assertArrayHasKey($wagonId, $wagons);
        $this->assertEquals(32, $wagons[$wagonId]->getSeatCount()->getSeats());
        $this->assertEquals(1.2, $wagons[$wagonId]->getSpeed()->getMetersPerSecond());
    }
}
