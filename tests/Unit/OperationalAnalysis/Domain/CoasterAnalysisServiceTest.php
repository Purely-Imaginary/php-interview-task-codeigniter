<?php

namespace Tests\Unit\OperationalAnalysis\Domain;

use App\OperationalAnalysis\Domain\CoasterAnalysisService;
use App\OperationalAnalysis\Domain\PersonnelAnalysisService;
use App\OperationalAnalysis\Domain\ThroughputAnalysisService;
use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\Wagon;
use App\Common\Domain\Distance;
use App\Common\Domain\TimeRange;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Unit\OperationalAnalysis\Domain\CoasterAnalysisServiceTestable;

class CoasterAnalysisServiceTest extends CIUnitTestCase
{
    /**
     * @var MockObject|PersonnelAnalysisService
     */
    private $personnelAnalysisService;

    /**
     * @var MockObject|ThroughputAnalysisService
     */
    private $throughputAnalysisService;

    /**
     * @var MockObject|\Redis
     */
    private $redis;

    /**
     * @var CoasterAnalysisService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->personnelAnalysisService = $this->createMock(PersonnelAnalysisService::class);
        $this->throughputAnalysisService = $this->createMock(ThroughputAnalysisService::class);
        $this->redis = $this->createMock(\Redis::class);

        // Create service with mocked dependencies
        $this->service = new CoasterAnalysisServiceTestable(
            $this->personnelAnalysisService,
            $this->throughputAnalysisService,
            $this->redis
        );
    }

    public function testAnalyzeAndPublishWithNoProblems()
    {
        $coaster = $this->createCoaster();

        // Set up personnel analysis result with no problems
        $personnelResult = [
            'required_personnel' => 7,
            'available_personnel' => 10,
            'shortage' => 0,
            'surplus' => 3,
            'has_shortage' => false,
            'has_surplus' => true
        ];

        // Set up throughput analysis result with no problems
        $throughputResult = [
            'total_seat_capacity' => 64,
            'total_trips_per_day' => 48,
            'total_clients_per_day' => 1536,
            'daily_clients_target' => 1000,
            'shortage' => 0,
            'surplus' => 0,
            'has_shortage' => false,
            'has_surplus' => false,
            'additional_wagons_needed' => 0
        ];

        $this->personnelAnalysisService->expects($this->once())
            ->method('analyze')
            ->with($coaster)
            ->willReturn($personnelResult);

        $this->throughputAnalysisService->expects($this->once())
            ->method('analyze')
            ->with($coaster)
            ->willReturn($throughputResult);

        // Expect Redis publish to operational_status_updates channel
        $this->redis->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo('operational_status_updates'),
                $this->callback(function ($message) {
                    $data = json_decode($message, true);
                    return $data['status'] === 'OK';
                })
            );

        // No publish to capacity_problems channel expected
        $this->redis->expects($this->exactly(1))
            ->method('publish');

        $result = $this->service->analyzeAndPublish($coaster);

        $this->assertEquals('coaster_123', $result['id']);
        $this->assertEquals('OK', $result['status']);
    }

    public function testAnalyzeAndPublishWithShortage()
    {
        $coaster = $this->createCoaster();

        // Set up personnel analysis result with shortage
        $personnelResult = [
            'required_personnel' => 7,
            'available_personnel' => 5,
            'shortage' => 2,
            'surplus' => 0,
            'has_shortage' => true,
            'has_surplus' => false
        ];

        // Set up throughput analysis result with no problems
        $throughputResult = [
            'total_seat_capacity' => 64,
            'total_trips_per_day' => 48,
            'total_clients_per_day' => 1536,
            'daily_clients_target' => 1000,
            'shortage' => 0,
            'surplus' => 0,
            'has_shortage' => false,
            'has_surplus' => false,
            'additional_wagons_needed' => 0
        ];

        $this->personnelAnalysisService->expects($this->once())
            ->method('analyze')
            ->with($coaster)
            ->willReturn($personnelResult);

        $this->throughputAnalysisService->expects($this->once())
            ->method('analyze')
            ->with($coaster)
            ->willReturn($throughputResult);

        // Expect Redis publish to operational_status_updates channel
        $this->redis->expects($this->at(0))
            ->method('publish')
            ->with(
                $this->equalTo('operational_status_updates'),
                $this->callback(function ($message) {
                    $data = json_decode($message, true);
                    return $data['status'] === 'PROBLEM! Resource shortage';
                })
            );

        // Expect Redis publish to capacity_problems channel
        $this->redis->expects($this->at(1))
            ->method('publish')
            ->with(
                $this->equalTo('capacity_problems'),
                $this->callback(function ($message) {
                    $data = json_decode($message, true);
                    return strpos($data['details'], 'Shortage of 2 staff') !== false;
                })
            );

        $result = $this->service->analyzeAndPublish($coaster);

        $this->assertEquals('coaster_123', $result['id']);
        $this->assertEquals('PROBLEM! Resource shortage', $result['status']);
    }

    public function testAnalyzeAndPublishWithSurplus()
    {
        $coaster = $this->createCoaster();

        // Set up personnel analysis result with surplus
        $personnelResult = [
            'required_personnel' => 7,
            'available_personnel' => 20,
            'shortage' => 0,
            'surplus' => 13,
            'has_shortage' => false,
            'has_surplus' => true
        ];

        // Set up throughput analysis result with surplus
        $throughputResult = [
            'total_seat_capacity' => 128,
            'total_trips_per_day' => 96,
            'total_clients_per_day' => 3072,
            'daily_clients_target' => 1000,
            'shortage' => 0,
            'surplus' => 1072,
            'has_shortage' => false,
            'has_surplus' => true,
            'additional_wagons_needed' => 0
        ];

        $this->personnelAnalysisService->expects($this->once())
            ->method('analyze')
            ->with($coaster)
            ->willReturn($personnelResult);

        $this->throughputAnalysisService->expects($this->once())
            ->method('analyze')
            ->with($coaster)
            ->willReturn($throughputResult);

        // Expect Redis publish to operational_status_updates channel
        $this->redis->expects($this->at(0))
            ->method('publish')
            ->with(
                $this->equalTo('operational_status_updates'),
                $this->callback(function ($message) {
                    $data = json_decode($message, true);
                    return $data['status'] === 'PROBLEM! Resource surplus';
                })
            );

        // Expect Redis publish to capacity_problems channel
        $this->redis->expects($this->at(1))
            ->method('publish')
            ->with(
                $this->equalTo('capacity_problems'),
                $this->callback(function ($message) {
                    $data = json_decode($message, true);
                    return strpos($data['details'], 'Surplus of 13 staff') !== false &&
                           strpos($data['details'], 'Excess capacity') !== false;
                })
            );

        $result = $this->service->analyzeAndPublish($coaster);

        $this->assertEquals('coaster_123', $result['id']);
        $this->assertEquals('PROBLEM! Resource surplus', $result['status']);
    }

    /**
     * Helper method to create a test coaster
     */
    private function createCoaster()
    {
        $coaster = new Coaster(
            'coaster_123',
            16,
            1000,
            new Distance(1800),
            new TimeRange('08:00', '16:00')
        );

        $wagon = Wagon::create(32, 2.0);
        $coaster->addWagon($wagon);

        // Clear events
        $coaster->releaseEvents();

        return $coaster;
    }
}
