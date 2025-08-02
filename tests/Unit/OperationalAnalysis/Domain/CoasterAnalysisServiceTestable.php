<?php

namespace Tests\Unit\OperationalAnalysis\Domain;

use App\OperationalAnalysis\Domain\CoasterAnalysisService;

/**
 * Testable version of CoasterAnalysisService that allows injecting mocks
 */
class CoasterAnalysisServiceTestable extends CoasterAnalysisService
{
    public function __construct($personnelAnalysisService, $throughputAnalysisService, $redis)
    {
        $this->personnelAnalysisService = $personnelAnalysisService;
        $this->throughputAnalysisService = $throughputAnalysisService;
        $this->redis = $redis;
    }
}
