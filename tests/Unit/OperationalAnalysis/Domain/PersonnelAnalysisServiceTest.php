<?php

namespace Tests\Unit\OperationalAnalysis\Domain;

use App\OperationalAnalysis\Domain\PersonnelAnalysisService;
use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\Wagon;
use App\Common\Domain\Distance;
use App\Common\Domain\TimeRange;
use CodeIgniter\Test\CIUnitTestCase;

class PersonnelAnalysisServiceTest extends CIUnitTestCase
{
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new PersonnelAnalysisService();
    }

    public function testAnalyzeWithNoWagons()
    {
        $coaster = $this->createCoaster(10, []);

        $result = $this->service->analyze($coaster);

        // With no wagons, only 1 staff member is required
        $this->assertEquals(1, $result['required_personnel']);
        $this->assertEquals(10, $result['available_personnel']);
        $this->assertEquals(0, $result['shortage']);
        $this->assertEquals(9, $result['surplus']);
        $this->assertFalse($result['has_shortage']);
        $this->assertTrue($result['has_surplus']);
    }

    public function testAnalyzeWithSufficientPersonnel()
    {
        // Create 3 wagons, which require 1 + (3 * 2) = 7 staff members
        $wagons = [
            Wagon::create(32, 1.2),
            Wagon::create(32, 1.2),
            Wagon::create(32, 1.2)
        ];

        $coaster = $this->createCoaster(10, $wagons);

        $result = $this->service->analyze($coaster);

        $this->assertEquals(7, $result['required_personnel']);
        $this->assertEquals(10, $result['available_personnel']);
        $this->assertEquals(0, $result['shortage']);
        $this->assertEquals(3, $result['surplus']);
        $this->assertFalse($result['has_shortage']);
        $this->assertTrue($result['has_surplus']);
    }

    public function testAnalyzeWithExactPersonnel()
    {
        // Create 3 wagons, which require 1 + (3 * 2) = 7 staff members
        $wagons = [
            Wagon::create(32, 1.2),
            Wagon::create(32, 1.2),
            Wagon::create(32, 1.2)
        ];

        $coaster = $this->createCoaster(7, $wagons);

        $result = $this->service->analyze($coaster);

        $this->assertEquals(7, $result['required_personnel']);
        $this->assertEquals(7, $result['available_personnel']);
        $this->assertEquals(0, $result['shortage']);
        $this->assertEquals(0, $result['surplus']);
        $this->assertFalse($result['has_shortage']);
        $this->assertFalse($result['has_surplus']);
    }

    public function testAnalyzeWithPersonnelShortage()
    {
        // Create 3 wagons, which require 1 + (3 * 2) = 7 staff members
        $wagons = [
            Wagon::create(32, 1.2),
            Wagon::create(32, 1.2),
            Wagon::create(32, 1.2)
        ];

        $coaster = $this->createCoaster(5, $wagons);

        $result = $this->service->analyze($coaster);

        $this->assertEquals(7, $result['required_personnel']);
        $this->assertEquals(5, $result['available_personnel']);
        $this->assertEquals(2, $result['shortage']);
        $this->assertEquals(0, $result['surplus']);
        $this->assertTrue($result['has_shortage']);
        $this->assertFalse($result['has_surplus']);
    }

    public function testCalculateAdditionalPersonnelNeeded()
    {
        // 3 wagons require 1 + (3 * 2) = 7 staff members
        // With 5 staff members, we need 2 more
        $this->assertEquals(2, $this->service->calculateAdditionalPersonnelNeeded(5, 3));

        // With 7 staff members, we need 0 more
        $this->assertEquals(0, $this->service->calculateAdditionalPersonnelNeeded(7, 3));

        // With 10 staff members, we need 0 more
        $this->assertEquals(0, $this->service->calculateAdditionalPersonnelNeeded(10, 3));
    }

    public function testCalculateMaxWagons()
    {
        // With 7 staff members, we can operate 3 wagons (1 for coaster, 6 for wagons)
        $this->assertEquals(3, $this->service->calculateMaxWagons(7));

        // With 5 staff members, we can operate 2 wagons (1 for coaster, 4 for wagons)
        $this->assertEquals(2, $this->service->calculateMaxWagons(5));

        // With 1 staff member, we can operate 0 wagons (1 for coaster, 0 for wagons)
        $this->assertEquals(0, $this->service->calculateMaxWagons(1));

        // With 3 staff members, we can operate 1 wagon (1 for coaster, 2 for wagons)
        $this->assertEquals(1, $this->service->calculateMaxWagons(3));
    }

    /**
     * Helper method to create a coaster with the given personnel count and wagons
     */
    private function createCoaster($personnelCount, $wagons)
    {
        $coaster = new Coaster(
            'coaster_123',
            $personnelCount,
            60000,
            new Distance(1800),
            new TimeRange('08:00', '16:00')
        );

        foreach ($wagons as $wagon) {
            $coaster->addWagon($wagon);
        }

        // Clear events
        $coaster->releaseEvents();

        return $coaster;
    }
}
