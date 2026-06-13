<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\GradeController;
use App\Services\GradeService;
use App\Models\Grade;
use App\Models\Room;
use App\Models\RoomItem;

function makeGradeController(): GradeController
{
    $db = getDb();
    return new GradeController(new GradeService(
        new Grade($db),
        new Room($db),
        new RoomItem($db)
    ));
}

$segments  = explode('/', ltrim($path, '/'));
$segment2  = $segments[2] ?? null; 
$segment3  = $segments[3] ?? null;
$studentId = isset($segments[4]) && is_numeric($segments[4]) ? (int)$segments[4] : null;

match (true) {

    $method === 'GET' && $path === '/api/grades' => (function () {
        AuthMiddleware::require('student');
        makeGradeController()->getMyGrades();
    })(),

    $method === 'GET' && is_numeric($segment2) && $segment3 === 'grades' && $studentId === null => (function () use ($segment2) {
        AuthMiddleware::require('teacher');
        makeGradeController()->getRoomGrades((int)$segment2);
    })(),

    $method === 'POST' && is_numeric($segment2) && $segment3 === 'grades' && $studentId === null => (function () use ($segment2) {
        AuthMiddleware::require('teacher');
        makeGradeController()->setGrade((int)$segment2);
    })(),

    $method === 'DELETE' && is_numeric($segment2) && $segment3 === 'grades' && $studentId !== null => (function () use ($segment2, $studentId) {
        AuthMiddleware::require('teacher');
        makeGradeController()->deleteGrade((int)$segment2, $studentId);
    })(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Grade route not found.']);
    })()
};