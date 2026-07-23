<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'AuthController::index');
$routes->post('/auth/login', 'AuthController::login');
$routes->get('/auth/logout', 'AuthController::logout');
