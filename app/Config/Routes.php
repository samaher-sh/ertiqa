<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'AuthController::index');
$routes->post('/auth/login', 'AuthController::login');
$routes->get('/auth/logout', 'AuthController::logout');

// نقاط API مشتركة تُستخدم من أكثر من صفحة داخل الـ SPA shell (كل واحدة تتحقق من الجلسة يدويًا وترجع 401)
$routes->get('api/session',     'ApiController::session');
$routes->get('api/nav-items',   'ApiController::navItems');
$routes->get('api/departments', 'ApiController::departments');

$routes->group('dashboard', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('api/home-stats',        'DashboardController::homeStats');
    $routes->get('api/target-missions',   'DashboardController::targetMissions');
    $routes->get('api/active-missions',   'DashboardController::activeMissions');
    $routes->get('api/scheduled-meetings','DashboardController::scheduledMeetings');
    $routes->post('new-task', 'MissionController::store');
    $routes->get('risk-matrix/api/items',  'RiskMatrixController::items');
    $routes->post('risk-matrix/api/save',  'RiskMatrixController::save');
    $routes->get('pdf/mission-letter/(:num)',   'PdfController::missionLetter/$1');
    $routes->get('pdf/risk-matrix/(:num)',      'PdfController::riskMatrix/$1');
    $routes->get('pdf/meeting-summary/(:num)',  'PdfController::meetingSummary/$1');
    $routes->get('meetings/api/data',           'MeetingSummaryController::data');
    $routes->post('meetings/api/save',          'MeetingSummaryController::save');
    $routes->post('meetings/api/upload',        'DocumentController::uploadMeetingAttachment');
    $routes->get('meetings/api/attachments',    'DocumentController::meetingAttachments');
    $routes->get('documents/download/(:num)',   'DocumentController::download/$1');
    $routes->get('document-requests/api/list',   'DocumentRequestController::list');
    $routes->post('document-requests/api/add',    'DocumentRequestController::add');
    $routes->post('document-requests/api/submit', 'DocumentRequestController::submit');
    $routes->get('target-mission/api/data',          'MissionReviewController::data');
    $routes->post('target-mission/api/save-agreement', 'MissionReviewController::saveAgreement');
    $routes->get('meeting-schedule/api/messages', 'MissionChatController::messages');
    $routes->post('meeting-schedule/api/send',    'MissionChatController::send');
    $routes->post('meeting-schedule/api/propose', 'MissionChatController::propose');
    $routes->post('meeting-schedule/api/confirm', 'MissionChatController::confirm');
    $routes->post('meeting-schedule/api/cancel',  'MissionChatController::cancel');
    $routes->post('meeting-schedule/api/cancel-confirmed', 'MissionChatController::cancelConfirmed');
    $routes->post('documents/delete/(:num)',    'DocumentController::delete/$1');
    $routes->get('observations/api/list',       'ObservationController::list');
    $routes->post('observations/api/save',      'ObservationController::save');
    $routes->post('observations/api/delete/(:num)', 'ObservationController::delete/$1');
    $routes->post('observations/api/status/(:num)', 'ObservationController::updateStatus/$1');
    $routes->get('reports/api/list',             'ReportController::list');
    $routes->get('reports/api/checklist',        'ReportController::checklist');
    $routes->get('reports/api/preview',          'ReportController::preview');
    $routes->post('reports/api/toggle-check',    'ReportController::toggleCheck');
    $routes->post('reports/api/finalize',        'ReportController::finalize');
    $routes->get('sent-tasks/api/timeline',      'SentTasksController::timeline');
});
