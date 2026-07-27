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
    $routes->get('new-task',  'MissionController::newTask');
    $routes->post('new-task', 'MissionController::store');
    $routes->get('risk-matrix',            'RiskMatrixController::index');
    $routes->get('risk-matrix/api/items',  'RiskMatrixController::items');
    $routes->post('risk-matrix/api/save',  'RiskMatrixController::save');
    $routes->get('pdf/mission-letter/(:num)',   'PdfController::missionLetter/$1');
    $routes->get('pdf/risk-matrix/(:num)',      'PdfController::riskMatrix/$1');
    $routes->get('pdf/meeting-summary/(:num)',  'PdfController::meetingSummary/$1');
    $routes->get('meetings',                    'MeetingSummaryController::index');
    $routes->get('meetings/api/data',           'MeetingSummaryController::data');
    $routes->post('meetings/api/save',          'MeetingSummaryController::save');
    $routes->post('meetings/api/upload',        'DocumentController::uploadMeetingAttachment');
    $routes->get('meetings/api/attachments',    'DocumentController::meetingAttachments');
    $routes->get('documents/download/(:num)',   'DocumentController::download/$1');
    $routes->post('documents/delete/(:num)',    'DocumentController::delete/$1');
    $routes->get('observations',                'ObservationController::index');
    $routes->get('observations/api/list',       'ObservationController::list');
    $routes->post('observations/api/save',      'ObservationController::save');
    $routes->post('observations/api/delete/(:num)', 'ObservationController::delete/$1');
    $routes->post('observations/api/status/(:num)', 'ObservationController::updateStatus/$1');
    $routes->get('reports',                      'ReportController::index');
    $routes->get('reports/api/checklist',        'ReportController::checklist');
    $routes->post('reports/api/toggle-check',    'ReportController::toggleCheck');
    $routes->post('reports/api/finalize',        'ReportController::finalize');
    $routes->get('sent-tasks',                   'SentTasksController::index');
    $routes->get('sent-tasks/api/timeline',      'SentTasksController::timeline');
});
