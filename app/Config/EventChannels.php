<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class EventChannels extends BaseConfig
{
    public const OPERATIONAL_STATUS_UPDATES = 'operational_status_updates';
    public const CAPACITY_PROBLEMS          = 'capacity_problems';
    public const CONFIGURATION_CHANGED      = 'coaster.configuration.changed';
}
