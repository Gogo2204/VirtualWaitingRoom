<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\StatisticsController;
use App\Services\StatisticsService;
use App\Models\Room;
use App\Models\RoomHistory;
use App\Models\RoomItem;
use App\Models\TeacherStudent;

function makeStatisticsController(): StatisticsController
{
    $db = getDb();
    return new StatisticsController(new StatisticsService(
        new Room($db),
        new RoomHistory($db),
        new RoomItem($db),
        new TeacherStudent($db)
    ));
}

$segments   = explode('/', ltrim($path, '/'));
$resource   = $segments[2] ?? null;
$resourceId = isset($segments[3]) && is_numeric($segments[3]) ? (int)$segments[3] : null;

match (true) {

    $method === 'GET' && $resource === 'rooms' && $resourceId !== null => (function () use ($resourceId) {
        AuthMiddleware::require('teacher', 'student', 'admin');
        makeStatisticsController()->roomStats($resourceId);
    })(),

    $method === 'GET' && $resource === 'subjects' => (function () {
        AuthMiddleware::require('teacher');
        makeStatisticsController()->subjectStats();
    })(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Stats route not found.']);
    })()
};
