<?php

namespace App\FleetManagement\Infrastructure\Controllers;

use CodeIgniter\Controller;

/**
 * Home Controller
 * Handles the default route
 */
class Home extends Controller
{
    /**
     * Display the welcome page
     *
     * @return string
     */
    public function index()
    {
        return $this->response->setBody($this->getWelcomeHtml());
    }

    /**
     * Get the welcome page HTML
     *
     * @return string
     */
    private function getWelcomeHtml()
    {
        $title = 'Amusement Park Coaster Management System';
        $description = 'A system for managing roller coasters and their assigned wagons in an amusement park.';

        $apiEndpoints = [
            [
                'method' => 'POST',
                'url' => '/api/coasters',
                'description' => 'Register a new coaster'
            ],
            [
                'method' => 'PUT',
                'url' => '/api/coasters/{coasterId}',
                'description' => 'Update an existing coaster'
            ],
            [
                'method' => 'POST',
                'url' => '/api/coasters/{coasterId}/wagons',
                'description' => 'Add a new wagon to a coaster'
            ],
            [
                'method' => 'DELETE',
                'url' => '/api/coasters/{coasterId}/wagons/{wagonId}',
                'description' => 'Remove a wagon from a coaster'
            ]
        ];

        $cliCommands = [
            [
                'command' => 'php spark monitor',
                'description' => 'Start the real-time monitoring dashboard'
            ]
        ];

        $endpointsHtml = '';
        foreach ($apiEndpoints as $endpoint) {
            $methodClass = strtolower($endpoint['method']);
            $endpointsHtml .= "
                <div class=\"endpoint\">
                    <span class=\"method {$methodClass}\">{$endpoint['method']}</span>
                    <span class=\"url\">{$endpoint['url']}</span>
                    <p>{$endpoint['description']}</p>
                </div>
            ";
        }

        $commandsHtml = '';
        foreach ($cliCommands as $command) {
            $commandsHtml .= "
                <div class=\"command\">
                    <code class=\"cmd\">{$command['command']}</code>
                    <p>{$command['description']}</p>
                </div>
            ";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #2980b9;
            margin-top: 30px;
        }
        .endpoint {
            background-color: #fff;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 5px 5px 0;
        }
        .method {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-right: 10px;
        }
        .post {
            background-color: #2ecc71;
            color: white;
        }
        .put {
            background-color: #f39c12;
            color: white;
        }
        .delete {
            background-color: #e74c3c;
            color: white;
        }
        .url {
            font-family: monospace;
            background-color: #f1f1f1;
            padding: 5px;
            border-radius: 3px;
        }
        .command {
            background-color: #fff;
            border-left: 4px solid #9b59b6;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 5px 5px 0;
        }
        .cmd {
            font-family: monospace;
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 10px;
            border-radius: 3px;
            display: block;
            margin-bottom: 10px;
        }
        footer {
            margin-top: 30px;
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{$title}</h1>
        <p>{$description}</p>

        <h2>API Endpoints</h2>
        {$endpointsHtml}

        <h2>CLI Commands</h2>
        {$commandsHtml}

        <h2>Architecture</h2>
        <p>This application is built following Domain-Driven Design (DDD) principles with the following bounded contexts:</p>
        <ul>
            <li><strong>Fleet Management Context:</strong> Responsible for CRUD operations on Coasters and Wagons.</li>
            <li><strong>Operational Analysis Context:</strong> Analyzes resource needs and detects operational problems.</li>
            <li><strong>Monitoring Context:</strong> Provides a real-time CLI dashboard for system status.</li>
        </ul>

        <footer>
            <p>Amusement Park Coaster Management System - Built with CodeIgniter 4</p>
        </footer>
    </div>
</body>
</html>
HTML;
    }
}
