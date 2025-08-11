<?php

namespace App\OperationalAnalysis\Application;

use App\FleetManagement\Domain\CoasterRepository;
use App\FleetManagement\Infrastructure\RedisCoasterRepository;
use App\OperationalAnalysis\Domain\CoasterAnalysisService;
use Config\Redis;
use Config\EventChannels;

/**
 * Coaster Configuration Changed Listener
 * Listens for coaster configuration changes and triggers analysis
 */
class CoasterConfigurationChangedListener
{
    /**
     * @var CoasterRepository
     */
    private $coasterRepository;

    /**
     * @var CoasterAnalysisService
     */
    private $analysisService;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * Constructor
     *
     * @param CoasterRepository $coasterRepository
     * @param CoasterAnalysisService $analysisService
     * @param \Redis $redis
     */
    public function __construct(
        CoasterRepository $coasterRepository = null,
        CoasterAnalysisService $analysisService = null,
        \Redis $redis = null
    ) {
        $this->coasterRepository = $coasterRepository ?? service('coasterRepository');
        $this->analysisService = $analysisService ?? service('coasterAnalysisService');

        if ($redis) {
            $this->redis = $redis;
        } else {
            // Connect to Redis
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
        }
    }

    /**
     * Start listening for events
     */
    public function listen()
    {
        // Subscribe to the coaster.configuration.changed channel
        $this->redis->subscribe([EventChannels::CONFIGURATION_CHANGED], [$this, 'handleEvent']);
    }

    /**
     * Handle the event
     *
     * @param \Redis $redis
     * @param string $channel
     * @param string $message
     */
    public function handleEvent($redis, $channel, $message)
    {
        $data = json_decode($message, true);

        if (!isset($data['coaster_id'])) {
            return;
        }

        $coasterId = $data['coaster_id'];
        $coaster = $this->coasterRepository->findById($coasterId);

        if (!$coaster) {
            return;
        }

        // Analyze the coaster and publish results
        $this->analysisService->analyzeAndPublish($coaster);
    }

    /**
     * Process a single coaster by ID
     *
     * @param string $coasterId
     * @return array|null
     */
    public function processCoaster($coasterId)
    {
        $coaster = $this->coasterRepository->findById($coasterId);

        if (!$coaster) {
            return null;
        }

        return $this->analysisService->analyzeAndPublish($coaster);
    }

    /**
     * Process all coasters
     *
     * @return array
     */
    public function processAllCoasters()
    {
        $coasters = $this->coasterRepository->findAll();
        $results = [];

        foreach ($coasters as $coaster) {
            $results[$coaster->getId()] = $this->analysisService->analyzeAndPublish($coaster);
        }

        return $results;
    }
}
