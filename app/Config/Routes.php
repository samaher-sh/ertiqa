<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'AuthController::index');
$routes->post('/auth/login', 'AuthController::login');
$routes->get('/auth/logout', 'AuthController::logout');

$routes->group('dashboard', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('api/home-stats',        'DashboardController::homeStats');
    $routes->get('api/active-missions',   'DashboardController::activeMissions');
    $routes->get('api/scheduled-meetings','DashboardController::scheduledMeetings');
});
