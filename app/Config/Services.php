<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Return an instance of the RedisCoasterRepository
     *
     * @param bool $getShared
     * @return \App\FleetManagement\Infrastructure\RedisCoasterRepository
     */
    public static function coasterRepository($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('coasterRepository');
        }

        return new \App\FleetManagement\Infrastructure\RedisCoasterRepository();
    }

    /**
     * Return an instance of the PersonnelAnalysisService
     *
     * @param bool $getShared
     * @return \App\OperationalAnalysis\Domain\PersonnelAnalysisService
     */
    public static function personnelAnalysisService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('personnelAnalysisService');
        }

        return new \App\OperationalAnalysis\Domain\PersonnelAnalysisService();
    }

    /**
     * Return an instance of the ThroughputAnalysisService
     *
     * @param bool $getShared
     * @return \App\OperationalAnalysis\Domain\ThroughputAnalysisService
     */
    public static function throughputAnalysisService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('throughputAnalysisService');
        }

        return new \App\OperationalAnalysis\Domain\ThroughputAnalysisService();
    }

    /**
     * Return an instance of the CoasterAnalysisService
     *
     * @param bool $getShared
     * @return \App\OperationalAnalysis\Domain\CoasterAnalysisService
     */
    public static function coasterAnalysisService($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('coasterAnalysisService');
        }

        $personnelAnalysisService = static::personnelAnalysisService();
        $throughputAnalysisService = static::throughputAnalysisService();

        return new \App\OperationalAnalysis\Domain\CoasterAnalysisService(
            $personnelAnalysisService,
            $throughputAnalysisService
        );
    }

    /**
     * Return an instance of the CoasterConfigurationChangedListener
     *
     * @param bool $getShared
     * @return \App\OperationalAnalysis\Application\CoasterConfigurationChangedListener
     */
    public static function coasterConfigurationChangedListener($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('coasterConfigurationChangedListener');
        }

        $coasterRepository = static::coasterRepository();
        $coasterAnalysisService = static::coasterAnalysisService();

        return new \App\OperationalAnalysis\Application\CoasterConfigurationChangedListener(
            $coasterRepository,
            $coasterAnalysisService
        );
    }
}
