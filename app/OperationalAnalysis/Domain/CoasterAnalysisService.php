<?php

namespace App\OperationalAnalysis\Domain;

use App\FleetManagement\Domain\Coaster;
use App\FleetManagement\Domain\CoasterRepository;
use Config\Redis;

/**
 * Coaster Analysis Service
 * Combines personnel and throughput analyses and publishes results
 */
class CoasterAnalysisService
{
    /**
     * @var PersonnelAnalysisService
     */
    protected $personnelAnalysisService;

    /**
     * @var ThroughputAnalysisService
     */
    protected $throughputAnalysisService;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param PersonnelAnalysisService $personnelAnalysisService
     * @param ThroughputAnalysisService $throughputAnalysisService
     * @param \Redis $redis
     */
    public function __construct(
        PersonnelAnalysisService $personnelAnalysisService = null,
        ThroughputAnalysisService $throughputAnalysisService = null,
        \Redis $redis = null
    ) {
        $this->personnelAnalysisService = $personnelAnalysisService ?? service('personnelAnalysisService');
        $this->throughputAnalysisService = $throughputAnalysisService ?? service('throughputAnalysisService');

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
     * Analyze a coaster and publish results
     *
     * @param Coaster $coaster
     * @return array
     */
    public function analyzeAndPublish(Coaster $coaster)
    {
        $personnelAnalysis = $this->personnelAnalysisService->analyze($coaster);
        $throughputAnalysis = $this->throughputAnalysisService->analyze($coaster);

        $status = $this->determineOperationalStatus($personnelAnalysis, $throughputAnalysis);

        $result = [
            'id' => $coaster->getId(),
            'operating_hours' => [
                'start' => $coaster->getOperatingHours()->getStart(),
                'end' => $coaster->getOperatingHours()->getEnd()
            ],
            'wagons' => [
                'count' => count($coaster->getWagons()),
                'total' => count($coaster->getWagons()) + $throughputAnalysis['additional_wagons_needed']
            ],
            'personnel' => [
                'available' => $personnelAnalysis['available_personnel'],
                'required' => $personnelAnalysis['required_personnel']
            ],
            'daily_clients' => $coaster->getDailyClients(),
            'status' => $status
        ];

        // Publish to operational_status_updates channel
        $this->redis->publish('operational_status_updates', json_encode($result));

        // If there's a problem, publish to capacity_problems channel
        if ($status !== 'OK') {
            $problemDetails = $this->generateProblemDetails($personnelAnalysis, $throughputAnalysis);
            $problemMessage = [
                'coaster_id' => $coaster->getId(),
                'timestamp' => time(),
                'status' => $status,
                'details' => $problemDetails
            ];

            $this->redis->publish('capacity_problems', json_encode($problemMessage));
        }

        return $result;
    }

    /**
     * Determine the operational status based on analyses
     *
     * @param array $personnelAnalysis
     * @param array $throughputAnalysis
     * @return string
     */
    private function determineOperationalStatus($personnelAnalysis, $throughputAnalysis)
    {
        if ($personnelAnalysis['has_shortage'] || $throughputAnalysis['has_shortage']) {
            return 'PROBLEM! Resource shortage';
        }

        if ($personnelAnalysis['has_surplus'] && $throughputAnalysis['has_surplus']) {
            return 'PROBLEM! Resource surplus';
        }

        return 'OK';
    }

    /**
     * Generate problem details for logging
     *
     * @param array $personnelAnalysis
     * @param array $throughputAnalysis
     * @return string
     */
    private function generateProblemDetails($personnelAnalysis, $throughputAnalysis)
    {
        $details = [];

        if ($personnelAnalysis['has_shortage']) {
            $details[] = "Shortage of {$personnelAnalysis['shortage']} staff";
        }

        if ($throughputAnalysis['has_shortage']) {
            $details[] = "Shortage of {$throughputAnalysis['additional_wagons_needed']} wagons";
        }

        if ($personnelAnalysis['has_surplus']) {
            $details[] = "Surplus of {$personnelAnalysis['surplus']} staff";
        }

        if ($throughputAnalysis['has_surplus']) {
            $details[] = "Excess capacity: can serve {$throughputAnalysis['total_clients_per_day']} clients " .
                "(target: {$throughputAnalysis['daily_clients_target']})";
        }

        return implode(', ', $details);
    }
}
