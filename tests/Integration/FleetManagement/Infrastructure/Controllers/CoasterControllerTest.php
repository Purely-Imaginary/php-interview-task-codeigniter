<?php

namespace Tests\Integration\FleetManagement\Infrastructure\Controllers;

use CodeIgniter\Test\FeatureTestCase;
use App\FleetManagement\Infrastructure\RedisCoasterRepository;

class CoasterControllerTest extends FeatureTestCase
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var RedisCoasterRepository
     */
    private $repository;

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
    }

    protected function tearDown()
    {
        // Clean up after tests
        $this->redis->flushDB();
        $this->redis->close();

        parent::tearDown();
    }

    public function testCreateCoaster()
    {
        $result = $this->post('/api/coasters', [
            'personnel_count' => 16,
            'daily_clients' => 60000,
            'track_length_meters' => 1800,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '16:00'
        ]);

        $result->assertStatus(201);
        $result->assertJSONFragment(['status' => 'success']);

        $response = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);

        // Verify the coaster was saved to Redis
        $coasterId = $response['data']['id'];
        $coaster = $this->repository->findById($coasterId);

        $this->assertNotNull($coaster);
        $this->assertEquals(16, $coaster->getPersonnelCount());
        $this->assertEquals(60000, $coaster->getDailyClients());
        $this->assertEquals(1800, $coaster->getTrackLength()->getMeters());
        $this->assertEquals('08:00', $coaster->getOperatingHours()->getStart());
        $this->assertEquals('16:00', $coaster->getOperatingHours()->getEnd());
    }

    public function testCreateCoasterWithInvalidData()
    {
        // Missing required fields
        $result = $this->post('/api/coasters', [
            'personnel_count' => 16
        ]);

        $result->assertStatus(400); // Bad Request

        // Invalid time format
        $result = $this->post('/api/coasters', [
            'personnel_count' => 16,
            'daily_clients' => 60000,
            'track_length_meters' => 1800,
            'operating_hours_start' => '8:00', // Missing leading zero
            'operating_hours_end' => '16:00'
        ]);

        $result->assertStatus(400); // Bad Request

        // Negative values
        $result = $this->post('/api/coasters', [
            'personnel_count' => -5,
            'daily_clients' => 60000,
            'track_length_meters' => 1800,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '16:00'
        ]);

        $result->assertStatus(400); // Bad Request
    }

    public function testUpdateCoaster()
    {
        // First create a coaster
        $createResult = $this->post('/api/coasters', [
            'personnel_count' => 16,
            'daily_clients' => 60000,
            'track_length_meters' => 1800,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '16:00'
        ]);

        $createResponse = json_decode($createResult->getJSON(), true);
        $coasterId = $createResponse['data']['id'];

        // Now update it
        $updateResult = $this->put("/api/coasters/{$coasterId}", [
            'personnel_count' => 18,
            'daily_clients' => 65000,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '17:00'
        ]);

        $updateResult->assertStatus(200);
        $updateResult->assertJSONFragment(['status' => 'success']);

        // Verify the coaster was updated in Redis
        $coaster = $this->repository->findById($coasterId);

        $this->assertNotNull($coaster);
        $this->assertEquals(18, $coaster->getPersonnelCount());
        $this->assertEquals(65000, $coaster->getDailyClients());
        $this->assertEquals(1800, $coaster->getTrackLength()->getMeters()); // Should not change
        $this->assertEquals('08:00', $coaster->getOperatingHours()->getStart());
        $this->assertEquals('17:00', $coaster->getOperatingHours()->getEnd());
    }

    public function testUpdateNonExistentCoaster()
    {
        $result = $this->put('/api/coasters/non_existent_id', [
            'personnel_count' => 18,
            'daily_clients' => 65000,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '17:00'
        ]);

        $result->assertStatus(404); // Not Found
    }

    public function testUpdateCoasterWithInvalidData()
    {
        // First create a coaster
        $createResult = $this->post('/api/coasters', [
            'personnel_count' => 16,
            'daily_clients' => 60000,
            'track_length_meters' => 1800,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '16:00'
        ]);

        $createResponse = json_decode($createResult->getJSON(), true);
        $coasterId = $createResponse['data']['id'];

        // Now update it with invalid data
        $updateResult = $this->put("/api/coasters/{$coasterId}", [
            'personnel_count' => -5, // Negative value
            'daily_clients' => 65000,
            'operating_hours_start' => '08:00',
            'operating_hours_end' => '17:00'
        ]);

        $updateResult->assertStatus(400); // Bad Request
    }
}
