<?php

namespace Tests\Integration\FleetManagement\Infrastructure;

use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\Wagon;
use App\FleetManagement\Infrastructure\RedisCoasterRepository;
use App\Common\Domain\Distance;
use App\Common\Domain\TimeRange;
use CodeIgniter\Test\CIUnitTestCase;

class RedisCoasterRepositoryTest extends CIUnitTestCase
{
    /**
     * @var RedisCoasterRepository
     */
    private $repository;

    /**
     * @var \Redis
     */
    private $redis;

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

    public function testSaveAndFindById()
    {
        $coaster = $this->createCoaster();

        $this->repository->save($coaster);

        $foundCoaster = $this->repository->findById($coaster->getId());

        $this->assertNotNull($foundCoaster);
        $this->assertEquals($coaster->getId(), $foundCoaster->getId());
        $this->assertEquals($coaster->getPersonnelCount(), $foundCoaster->getPersonnelCount());
        $this->assertEquals($coaster->getDailyClients(), $foundCoaster->getDailyClients());
        $this->assertEquals($coaster->getTrackLength()->getMeters(), $foundCoaster->getTrackLength()->getMeters());
        $this->assertEquals($coaster->getOperatingHours()->getStart(), $foundCoaster->getOperatingHours()->getStart());
        $this->assertEquals($coaster->getOperatingHours()->getEnd(), $foundCoaster->getOperatingHours()->getEnd());
    }

    public function testFindByIdNonExistent()
    {
        $foundCoaster = $this->repository->findById('non_existent_id');

        $this->assertNull($foundCoaster);
    }

    public function testSaveWithWagons()
    {
        $coaster = $this->createCoaster();

        // Add wagons
        $wagon1 = Wagon::create(32, 1.2);
        $wagon2 = Wagon::create(24, 1.5);

        $coaster->addWagon($wagon1);
        $coaster->addWagon($wagon2);

        $this->repository->save($coaster);

        $foundCoaster = $this->repository->findById($coaster->getId());

        $this->assertNotNull($foundCoaster);

        $wagons = $foundCoaster->getWagons();
        $this->assertCount(2, $wagons);

        $this->assertArrayHasKey($wagon1->getId(), $wagons);
        $this->assertArrayHasKey($wagon2->getId(), $wagons);

        $this->assertEquals(32, $wagons[$wagon1->getId()]->getSeatCount()->getSeats());
        $this->assertEquals(1.2, $wagons[$wagon1->getId()]->getSpeed()->getMetersPerSecond());

        $this->assertEquals(24, $wagons[$wagon2->getId()]->getSeatCount()->getSeats());
        $this->assertEquals(1.5, $wagons[$wagon2->getId()]->getSpeed()->getMetersPerSecond());
    }

    public function testFindAll()
    {
        // Create and save multiple coasters
        $coaster1 = $this->createCoaster('coaster_1');
        $coaster2 = $this->createCoaster('coaster_2');
        $coaster3 = $this->createCoaster('coaster_3');

        $this->repository->save($coaster1);
        $this->repository->save($coaster2);
        $this->repository->save($coaster3);

        $allCoasters = $this->repository->findAll();

        $this->assertCount(3, $allCoasters);

        // Check that all coasters are found
        $foundIds = array_map(function ($coaster) {
            return $coaster->getId();
        }, $allCoasters);

        $this->assertContains($coaster1->getId(), $foundIds);
        $this->assertContains($coaster2->getId(), $foundIds);
        $this->assertContains($coaster3->getId(), $foundIds);
    }

    public function testDelete()
    {
        $coaster = $this->createCoaster();

        $this->repository->save($coaster);

        // Verify it was saved
        $foundCoaster = $this->repository->findById($coaster->getId());
        $this->assertNotNull($foundCoaster);

        // Delete it
        $this->repository->delete($coaster->getId());

        // Verify it was deleted
        $foundCoaster = $this->repository->findById($coaster->getId());
        $this->assertNull($foundCoaster);

        // Verify it's not in the list of all coasters
        $allCoasters = $this->repository->findAll();
        $foundIds = array_map(function ($coaster) {
            return $coaster->getId();
        }, $allCoasters);

        $this->assertNotContains($coaster->getId(), $foundIds);
    }

    public function testPublishEvents()
    {
        // This is a bit tricky to test since we need to subscribe to Redis channels
        // For simplicity, we'll just verify that the method doesn't throw exceptions

        $coaster = $this->createCoaster();

        // Get events from the coaster
        $events = $coaster->releaseEvents();

        // This should not throw an exception
        $this->repository->publishEvents($events);

        $this->assertTrue(true); // If we got here, the test passed
    }

    /**
     * Helper method to create a test coaster
     */
    private function createCoaster($id = 'coaster_test')
    {
        return new Coaster(
            $id,
            16,
            60000,
            new Distance(1800),
            new TimeRange('08:00', '16:00')
        );
    }
}
