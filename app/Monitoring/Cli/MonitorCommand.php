<?php

namespace App\Monitoring\Cli;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use React\EventLoop\Factory;
use Clue\React\Redis\Factory as RedisFactory;
use Config\Redis;

/**
 * Monitor Command
 * CLI dashboard for real-time monitoring of coaster status
 */
class MonitorCommand extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Monitoring';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'monitor';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Real-time monitoring dashboard for coaster status';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'monitor';

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
     * @var array
     */
    private $coasterStatus = [];

    /**
     * @var string
     */
    private $logFile = WRITEPATH . 'logs/notifications.log';

    /**
     * Actually execute the command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::write('Starting Real-Time Coaster Status Monitor...', 'green');
        CLI::write('Press Ctrl+C to exit', 'yellow');
        CLI::newLine();

        // Create event loop
        $loop = Factory::create();

        // Get Redis configuration
        $config = new Redis();

        // Create Redis client factory
        $factory = new RedisFactory($loop);

        // Redis connection URI
        $redisUri = "redis://{$config->host}:{$config->port}";
        if (!empty($config->password)) {
            $redisUri = "redis://:" . urlencode($config->password) . "@{$config->host}:{$config->port}";
        }

        // Connect to Redis
        $client = $factory->createLazyClient($redisUri);

        // Select the correct database
        $client->select($config->database);

        // Subscribe to operational_status_updates channel
        $client->subscribe('operational_status_updates')->then(function () {
            CLI::write('Subscribed to operational_status_updates channel', 'green');
        }, function ($error) {
            CLI::error("Error subscribing to channel: " . $error->getMessage());
        });

        // Subscribe to capacity_problems channel
        $client->subscribe('capacity_problems')->then(function () {
            CLI::write('Subscribed to capacity_problems channel', 'green');
        }, function ($error) {
            CLI::error("Error subscribing to channel: " . $error->getMessage());
        });

        // Handle messages
        $client->on('message', function ($channel, $message) {
            $data = json_decode($message, true);

            if ($channel === 'operational_status_updates') {
                $this->updateCoasterStatus($data);
                $this->displayDashboard();
            } elseif ($channel === 'capacity_problems') {
                $this->logProblem($data);
            }
        });

        // Refresh the display every 5 seconds even if no updates
        $loop->addPeriodicTimer(5, function () {
            $this->displayDashboard();
        });

        // Run the loop
        $loop->run();
    }

    /**
     * Update coaster status
     *
     * @param array $data
     */
    private function updateCoasterStatus($data)
    {
        if (isset($data['id'])) {
            $this->coasterStatus[$data['id']] = $data;
        }
    }

    /**
     * Display the dashboard
     */
    private function displayDashboard()
    {
        // Clear the screen
        CLI::clearScreen();

        CLI::write('-- Real-Time Coaster Status [Last Update: ' . date('H:i:s') . '] --', 'green');
        CLI::newLine();

        if (empty($this->coasterStatus)) {
            CLI::write('No coasters available. Waiting for data...', 'yellow');
            return;
        }

        foreach ($this->coasterStatus as $coasterId => $status) {
            $statusColor = $status['status'] === 'OK' ? 'green' : 'red';

            CLI::write("[Coaster {$coasterId}]", 'cyan');
            $hours = "{$status['operating_hours']['start']} - {$status['operating_hours']['end']}";
            CLI::write("  Operating Hours: {$hours}");
            CLI::write("  Wagons: {$status['wagons']['count']}/{$status['wagons']['total']}");
            CLI::write("  Available Personnel: {$status['personnel']['available']}/{$status['personnel']['required']}");
            CLI::write("  Daily Clients: {$status['daily_clients']}");
            CLI::write("  Status: ", 'white', false);
            CLI::write($status['status'], $statusColor);
            CLI::newLine();
        }
    }

    /**
     * Log a problem
     *
     * @param array $data
     */
    private function logProblem($data)
    {
        $timestamp = date('Y-m-d H:i:s', $data['timestamp']);
        $coasterId = $data['coaster_id'];
        $details = $data['details'];

        $logMessage = "[{$timestamp}] Coaster {$coasterId} - Problem: {$details}" . PHP_EOL;

        // Ensure the logs directory exists
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0777, true);
        }

        // Append to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);

        // Also display in console
        CLI::write($logMessage, 'yellow');
    }
}
