<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API Routes Group with Authentication Filter
$routes->group('api', ['filter' => 'apiauth'], static function ($routes) {
    $routes->post('coasters', 'CoasterController::create');
    $routes->put('coasters/(:segment)', 'CoasterController::update/$1');
    $routes->post('coasters/(:segment)/wagons', 'WagonController::create/$1');
    $routes->delete('coasters/(:segment)/wagons/(:segment)', 'WagonController::delete/$1/$2');
});
