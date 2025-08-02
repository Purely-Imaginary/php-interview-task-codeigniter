<?php

namespace App\FleetManagement\Infrastructure;

use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\CoasterRepository;
use Config\Redis;

/**
 * Redis Coaster Repository
 * Implementation of the CoasterRepository interface using Redis
 */
class RedisCoasterRepository implements CoasterRepository
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $keyPrefix;

    /**
     * Constructor
     */
    public function __construct()
    {
        $config = new Redis();
        $this->redis = new \Redis();
        $this->redis->connect(
            $config->host,
            $config->port
        );

        if (!empty($config->password)) {
            $this->redis->auth($config->password);
        }

        $this->redis->select($config->database);

        // Use different key prefixes for different environments
        $this->keyPrefix = 'coaster:' . (getenv('APP_ENVIRONMENT') === 'production' ? 'prod:' : 'dev:');
    }

    /**
     * Save a coaster
     *
     * @param Coaster $coaster
     * @return void
     */
    public function save(Coaster $coaster)
    {
        $key = $this->keyPrefix . $coaster->getId();
        $data = json_encode($coaster->toArray());

        $this->redis->set($key, $data);

        // Add to index
        $this->redis->sAdd($this->keyPrefix . 'all', $coaster->getId());

        // Publish events
        $this->publishEvents($coaster->releaseEvents());
    }

    /**
     * Find a coaster by ID
     *
     * @param string $id
     * @return Coaster|null
     */
    public function findById($id)
    {
        $key = $this->keyPrefix . $id;
        $data = $this->redis->get($key);

        if (!$data) {
            return null;
        }

        return Coaster::fromArray(json_decode($data, true));
    }

    /**
     * Find all coasters
     *
     * @return array
     */
    public function findAll()
    {
        $ids = $this->redis->sMembers($this->keyPrefix . 'all');
        $coasters = [];

        foreach ($ids as $id) {
            $coaster = $this->findById($id);
            if ($coaster) {
                $coasters[] = $coaster;
            }
        }

        return $coasters;
    }

    /**
     * Delete a coaster
     *
     * @param string $id
     * @return void
     */
    public function delete($id)
    {
        $key = $this->keyPrefix . $id;
        $this->redis->del($key);
        $this->redis->sRem($this->keyPrefix . 'all', $id);
    }

    /**
     * Publish domain events
     *
     * @param array $events
     * @return void
     */
    public function publishEvents(array $events)
    {
        foreach ($events as $event) {
            $eventData = [
                'name' => $event->getEventName(),
                'data' => $event->getEventData(),
                'timestamp' => $event->getTimestamp()
            ];

            $this->redis->publish('domain_events', json_encode($eventData));

            // Publish to specific channel for the event
            $this->redis->publish($event->getEventName(), json_encode($event->getEventData()));
        }
    }
}
