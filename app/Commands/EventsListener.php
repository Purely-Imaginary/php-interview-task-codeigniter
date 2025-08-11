<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Events Listener Command
 * Listens for domain events and triggers appropriate handlers
 */
class EventsListener extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Events';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'events:listen';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Listen for domain events and trigger appropriate handlers';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'events:listen';

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
        CLI::write('Starting Event Listener...', 'green');
        CLI::newLine();

        CLI::write('Listening for events on channel: coaster.configuration.changed', 'yellow');
        CLI::newLine();

        // Get the listener service from the container
        $listener = service('coasterConfigurationChangedListener');

        try {
            // This will block and process events as they come in
            CLI::write('Waiting for events... Press Ctrl+C to stop', 'yellow');
            $listener->listen();
        } catch (\Exception $e) {
            CLI::write('Error: ' . $e->getMessage(), 'red');
        }
    }
}
