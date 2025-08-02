<?php

namespace Tests\Unit\OperationalAnalysis\Domain;

use App\OperationalAnalysis\Domain\ThroughputAnalysisService;
use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\Wagon;
use App\Common\Domain\Distance;
use App\Common\Domain\TimeRange;
use CodeIgniter\Test\CIUnitTestCase;

class ThroughputAnalysisServiceTest extends CIUnitTestCase
{
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new ThroughputAnalysisService();
    }

    public function testAnalyzeWithNoWagons()
    {
        $coaster = $this->createCoaster(10000, []);

        $result = $this->service->analyze($coaster);

        // With no wagons, no clients can be served
        $this->assertEquals(0, $result['total_seat_capacity']);
        $this->assertEquals(0, $result['total_trips_per_day']);
        $this->assertEquals(0, $result['total_clients_per_day']);
        $this->assertEquals(10000, $result['daily_clients_target']);
        $this->assertEquals(10000, $result['shortage']);
        $this->assertEquals(0, $result['surplus']);
        $this->assertTrue($result['has_shortage']);
        $this->assertFalse($result['has_surplus']);
    }

    public function testAnalyzeWithSufficientCapacity()
    {
        // Create 2 wagons with 32 seats each
        $wagons = [
            Wagon::create(32, 2.0), // 2 m/s
            Wagon::create(32, 2.0)  // 2 m/s
        ];

        // 8-hour operating day (480 minutes)
        // Track length: 1800 meters
        // Speed: 2 m/s
        // Trip time: 1800/2 = 900 seconds = 15 minutes
        // Total cycle time: 15 + 5 = 20 minutes
        // Trips per day per wagon: 480 / 20 = 24
        // Total trips: 24 * 2 = 48
        // Total clients: 48 * 32 = 1536

        $coaster = $this->createCoaster(1000, $wagons);

        $result = $this->service->analyze($coaster);

        $this->assertEquals(64, $result['total_seat_capacity']);
        $this->assertEquals(48, $result['total_trips_per_day']);
        $this->assertEquals(1536, $result['total_clients_per_day']);
        $this->assertEquals(1000, $result['daily_clients_target']);
        $this->assertEquals(0, $result['shortage']);
        $this->assertEquals(0, $result['surplus']); // Not more than twice the target
        $this->assertFalse($result['has_shortage']);
        $this->assertFalse($result['has_surplus']);
    }

    public function testAnalyzeWithCapacityShortage()
    {
        // Create 1 wagon with 32 seats
        $wagons = [
            Wagon::create(32, 2.0) // 2 m/s
        ];

        // 8-hour operating day (480 minutes)
        // Track length: 1800 meters
        // Speed: 2 m/s
        // Trip time: 1800/2 = 900 seconds = 15 minutes
        // Total cycle time: 15 + 5 = 20 minutes
        // Trips per day per wagon: 480 / 20 = 24
        // Total trips: 24 * 1 = 24
        // Total clients: 24 * 32 = 768

        $coaster = $this->createCoaster(1000, $wagons);

        $result = $this->service->analyze($coaster);

        $this->assertEquals(32, $result['total_seat_capacity']);
        $this->assertEquals(24, $result['total_trips_per_day']);
        $this->assertEquals(768, $result['total_clients_per_day']);
        $this->assertEquals(1000, $result['daily_clients_target']);
        $this->assertEquals(232, $result['shortage']);
        $this->assertEquals(0, $result['surplus']);
        $this->assertTrue($result['has_shortage']);
        $this->assertFalse($result['has_surplus']);

        // Should recommend adding more wagons
        $this->assertGreaterThan(0, $result['additional_wagons_needed']);
    }

    public function testAnalyzeWithCapacitySurplus()
    {
        // Create 4 wagons with 32 seats each
        $wagons = [
            Wagon::create(32, 2.0), // 2 m/s
            Wagon::create(32, 2.0), // 2 m/s
            Wagon::create(32, 2.0), // 2 m/s
            Wagon::create(32, 2.0)  // 2 m/s
        ];

        // 8-hour operating day (480 minutes)
        // Track length: 1800 meters
        // Speed: 2 m/s
        // Trip time: 1800/2 = 900 seconds = 15 minutes
        // Total cycle time: 15 + 5 = 20 minutes
        // Trips per day per wagon: 480 / 20 = 24
        // Total trips: 24 * 4 = 96
        // Total clients: 96 * 32 = 3072

        $coaster = $this->createCoaster(1000, $wagons);

        $result = $this->service->analyze($coaster);

        $this->assertEquals(128, $result['total_seat_capacity']);
        $this->assertEquals(96, $result['total_trips_per_day']);
        $this->assertEquals(3072, $result['total_clients_per_day']);
        $this->assertEquals(1000, $result['daily_clients_target']);
        $this->assertEquals(0, $result['shortage']);
        $this->assertEquals(1072, $result['surplus']); // More than twice the target (2000)
        $this->assertFalse($result['has_shortage']);
        $this->assertTrue($result['has_surplus']);
    }

    public function testCalculateClientsPerDay()
    {
        // 480 minutes operating time, 32 seats, 20 minutes per trip
        $this->assertEquals(768, $this->service->calculateClientsPerDay(480, 32, 20));

        // 600 minutes operating time, 48 seats, 30 minutes per trip
        $this->assertEquals(960, $this->service->calculateClientsPerDay(600, 48, 30));

        // 720 minutes operating time, 24 seats, 15 minutes per trip
        $this->assertEquals(1152, $this->service->calculateClientsPerDay(720, 24, 15));
    }

    /**
     * Helper method to create a coaster with the given daily clients and wagons
     */
    private function createCoaster($dailyClients, $wagons)
    {
        $coaster = new Coaster(
            'coaster_123',
            16,
            $dailyClients,
            new Distance(1800),
            new TimeRange('08:00', '16:00') // 8 hours = 480 minutes
        );

        foreach ($wagons as $wagon) {
            $coaster->addWagon($wagon);
        }

        // Clear events
        $coaster->releaseEvents();

        return $coaster;
    }
}
