<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Redis extends BaseConfig
{
    /**
     * Redis server host
     */
    public $host = 'redis';

    /**
     * Redis server port
     */
    public $port = 6379;

    /**
     * Redis server password (if any)
     */
    public $password = null;

    /**
     * Redis database index
     * Use database 0 for production, 1 for development
     */
    public $database = 1;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Override default values with environment variables if they exist
        if (getenv('redis.default.host') !== false) {
            $this->host = getenv('redis.default.host');
        }

        if (getenv('redis.default.port') !== false) {
            $this->port = (int)getenv('redis.default.port');
        }

        if (getenv('redis.default.password') !== false) {
            $this->password = getenv('redis.default.password');
        }

        // Set database based on environment
        // Use database 0 for production, 1 for development
        $environment = getenv('APP_ENVIRONMENT') ?: 'development';
        $this->database = ($environment === 'production') ? 0 : 1;

        // Override with specific database setting if it exists
        if (getenv('redis.default.database') !== false) {
            $this->database = (int)getenv('redis.default.database');
        }
    }
}
