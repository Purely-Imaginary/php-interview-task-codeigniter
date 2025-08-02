<?php

namespace Tests\Integration\FleetManagement\Infrastructure\Controllers;

use CodeIgniter\Test\FeatureTestCase;
use App\FleetManagement\Infrastructure\RedisCoasterRepository;

class WagonControllerTest extends FeatureTestCase
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var RedisCoasterRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $coasterId;

    protected function setUp()
    {
        parent::setUp();

        // Connect to Redis
        $this->redis = new \Redis();
        $this->redis->connect('redis', 6379);
        $this->redis->select(1); // Use test database

        // Clear test database
        $this->redis->flushDB();

        $this->repository = new RedisCoasterRepository();

        // Create a test coaster for all tests
        $createResult = $this->post('/api/coasters', [
            'personnel_count' => 16,
            'daily_clients' => 60000,
            'track_length_meters' => 1800,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '16:00'
        ]);

        $createResponse = json_decode($createResult->getJSON(), true);
        $this->coasterId = $createResponse['data']['id'];
    }

    protected function tearDown()
    {
        // Clean up after tests
        $this->redis->flushDB();
        $this->redis->close();

        parent::tearDown();
    }

    public function testCreateWagon()
    {
        $result = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => 32,
            'speed_mps' => 1.2
        ]);

        $result->assertStatus(201);
        $result->assertJSONFragment(['status' => 'success']);

        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);

        // Verify the wagon was added to the coaster in Redis
        $coaster = $this->repository->findById($this->coasterId);
        $wagons = $coaster->getWagons();

        $this->assertCount(1, $wagons);

        $wagonId = $response['data']['id'];
        $this->assertArrayHasKey($wagonId, $wagons);

        $wagon = $wagons[$wagonId];
        $this->assertEquals(32, $wagon->getSeatCount()->getSeats());
        $this->assertEquals(1.2, $wagon->getSpeed()->getMetersPerSecond());
    }

    public function testCreateWagonForNonExistentCoaster()
    {
        $result = $this->post('/api/coasters/non_existent_id/wagons', [
            'seat_count' => 32,
            'speed_mps' => 1.2
        ]);

        $result->assertStatus(404); // Not Found
    }

    public function testCreateWagonWithInvalidData()
    {
        // Missing required fields
        $result = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => 32
            // Missing speed_mps
        ]);

        $result->assertStatus(400); // Bad Request

        // Negative values
        $result = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => -5, // Negative value
            'speed_mps' => 1.2
        ]);

        $result->assertStatus(400); // Bad Request

        // Non-numeric values
        $result = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => 32,
            'speed_mps' => 'not a number'
        ]);

        $result->assertStatus(400); // Bad Request
    }

    public function testDeleteWagon()
    {
        // First create a wagon
        $createResult = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => 32,
            'speed_mps' => 1.2
        ]);

        $createResponse = json_decode($createResult->getJSON(), true);
        $wagonId = $createResponse['data']['id'];

        // Verify the wagon was added
        $coaster = $this->repository->findById($this->coasterId);
        $wagons = $coaster->getWagons();
        $this->assertCount(1, $wagons);
        $this->assertArrayHasKey($wagonId, $wagons);

        // Now delete it
        $deleteResult = $this->delete("/api/coasters/{$this->coasterId}/wagons/{$wagonId}");

        $deleteResult->assertStatus(200);
        $deleteResult->assertJSONFragment(['status' => 'success']);

        // Verify the wagon was removed
        $coaster = $this->repository->findById($this->coasterId);
        $wagons = $coaster->getWagons();
        $this->assertCount(0, $wagons);
    }

    public function testDeleteNonExistentWagon()
    {
        $result = $this->delete("/api/coasters/{$this->coasterId}/wagons/non_existent_id");

        $result->assertStatus(404); // Not Found
    }

    public function testDeleteWagonFromNonExistentCoaster()
    {
        $result = $this->delete('/api/coasters/non_existent_id/wagons/some_wagon_id');

        $result->assertStatus(404); // Not Found
    }

    public function testMultipleWagons()
    {
        // Create first wagon
        $createResult1 = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => 32,
            'speed_mps' => 1.2
        ]);

        $createResponse1 = json_decode($createResult1->getJSON(), true);
        $wagonId1 = $createResponse1['data']['id'];

        // Create second wagon
        $createResult2 = $this->post("/api/coasters/{$this->coasterId}/wagons", [
            'seat_count' => 24,
            'speed_mps' => 1.5
        ]);

        $createResponse2 = json_decode($createResult2->getJSON(), true);
        $wagonId2 = $createResponse2['data']['id'];

        // Verify both wagons were added
        $coaster = $this->repository->findById($this->coasterId);
        $wagons = $coaster->getWagons();

        $this->assertCount(2, $wagons);
        $this->assertArrayHasKey($wagonId1, $wagons);
        $this->assertArrayHasKey($wagonId2, $wagons);

        // Verify the properties of each wagon
        $this->assertEquals(32, $wagons[$wagonId1]->getSeatCount()->getSeats());
        $this->assertEquals(1.2, $wagons[$wagonId1]->getSpeed()->getMetersPerSecond());

        $this->assertEquals(24, $wagons[$wagonId2]->getSeatCount()->getSeats());
        $this->assertEquals(1.5, $wagons[$wagonId2]->getSpeed()->getMetersPerSecond());

        // Delete the first wagon
        $deleteResult = $this->delete("/api/coasters/{$this->coasterId}/wagons/{$wagonId1}");
        $deleteResult->assertStatus(200);

        // Verify only the second wagon remains
        $coaster = $this->repository->findById($this->coasterId);
        $wagons = $coaster->getWagons();

        $this->assertCount(1, $wagons);
        $this->assertArrayNotHasKey($wagonId1, $wagons);
        $this->assertArrayHasKey($wagonId2, $wagons);
    }
}
