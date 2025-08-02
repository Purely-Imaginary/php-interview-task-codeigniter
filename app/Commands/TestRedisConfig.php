<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Redis;

/**
 * Test Redis Configuration Command
 * Tests the Redis configuration in different environments
 */
class TestRedisConfig extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Testing';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'test:redis';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Test Redis configuration in different environments';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'test:redis';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute the command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::write('Testing Redis Configuration', 'green');
        CLI::newLine();

        // Get current environment
        $environment = getenv('APP_ENVIRONMENT') ?: 'development';
        CLI::write("Current Environment: {$environment}", 'yellow');

        // Get Redis configuration
        $config = new Redis();
        CLI::write("Redis Host: {$config->host}");
        CLI::write("Redis Port: {$config->port}");
        CLI::write("Redis Database: {$config->database}");

        // Connect to Redis
        try {
            $redis = new \Redis();
            $redis->connect($config->host, $config->port);

            if (!empty($config->password)) {
                $redis->auth($config->password);
            }

            $redis->select($config->database);

            // Test connection
            $redis->set('test_key', 'test_value');
            $value = $redis->get('test_key');

            if ($value === 'test_value') {
                CLI::write('Redis Connection: SUCCESS', 'green');
            } else {
                CLI::write('Redis Connection: FAILED - Could not retrieve test value', 'red');
            }

            // Clean up
            $redis->del('test_key');
        } catch (\Exception $e) {
            CLI::write('Redis Connection: FAILED - ' . $e->getMessage(), 'red');
        }

        CLI::newLine();
        CLI::write('To test in production mode, run:', 'yellow');
        CLI::write('APP_ENVIRONMENT=production php spark test:redis', 'yellow');
    }
}
