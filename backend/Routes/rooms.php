<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\RoomController;
use App\Controllers\CommentController;
use App\Services\RoomService;
use App\Services\CommentService;
use App\Models\Room;
use App\Models\RoomItem;
use App\Models\Comment;
use App\Models\Subject;
use App\Models\TeacherStudent;
use App\Models\RoomHistory;

function makeCommentController(): CommentController
{
    $db = getDb();
    return new CommentController(new CommentService(
        new Comment($db),
        new RoomItem($db)
    ));
}

function makeRoomController(): RoomController
{
    $db = getDb();
    return new RoomController(new RoomService(
        new Room($db),
        new RoomItem($db),
        new Comment($db),
        new Subject($db),
        new TeacherStudent($db),
        new RoomHistory($db)
    ));
}

$segments  = explode('/', ltrim($path, '/'));
$roomId    = isset($segments[2]) && is_numeric($segments[2]) ? (int)$segments[2] : null;
$segment3  = $segments[3] ?? null;
$segment4  = $segments[4] ?? null;
$itemId    = isset($segments[4]) && is_numeric($segments[4]) && (int)$segments[4] > 0 ? (int)$segments[4] : null;
$segment5  = $segments[5] ?? null;

match (true) {

    $method === 'POST' && $path === '/api/rooms' => (function () {
        AuthMiddleware::require('teacher');
        makeRoomController()->createRoom();
    })(),

    $method === 'GET' && $path === '/api/rooms' => (function () {
        AuthMiddleware::require('teacher', 'student');
        makeRoomController()->listRooms();
    })(),

    $method === 'GET' && $roomId !== null && $segment3 === null => (function () use ($roomId) {
        AuthMiddleware::require('teacher', 'student', 'admin');
        makeRoomController()->getRoom($roomId);
    })(),

    $method === 'PATCH' && $roomId !== null && $segment3 === 'status' => (function () use ($roomId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->updateRoomStatus($roomId);
    })(),

    $method === 'GET' && $roomId !== null && $segment3 === 'queue' && $itemId === null => (function () use ($roomId) {
        AuthMiddleware::require('teacher', 'student');
        makeRoomController()->getQueue($roomId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $segment4 === 'invite-all-temp' => (function () use ($roomId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->inviteAllTemp($roomId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $segment4 === 'return-all' => (function () use ($roomId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->returnAll($roomId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $segment4 === 'set-eta-all' => (function () use ($roomId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->setEtaAll($roomId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $itemId === null && $segment4 === null => (function () use ($roomId) {
        AuthMiddleware::require('student');
        makeRoomController()->joinQueue($roomId);
    })(),

    $method === 'DELETE' && $roomId !== null && $segment3 === 'queue' && $itemId === null => (function () use ($roomId) {
        AuthMiddleware::require('student');
        makeRoomController()->leaveQueue($roomId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $itemId !== null && $segment5 === 'invite' => (function () use ($roomId, $itemId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->inviteStudent($roomId, $itemId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $itemId !== null && $segment5 === 'finish' => (function () use ($roomId, $itemId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->finishMeeting($itemId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $itemId !== null && $segment5 === 'return' => (function () use ($roomId, $itemId) {
        AuthMiddleware::require('student', 'teacher');
        makeRoomController()->studentReturns($roomId, $itemId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $itemId !== null && $segment5 === 'slot' => (function () use ($roomId, $itemId) {
        AuthMiddleware::require('teacher');
        makeRoomController()->setManualSlot($roomId, $itemId);
    })(),

    $method === 'POST' && $roomId !== null && $segment3 === 'queue' && $itemId !== null && $segment5 === 'comments' => (function () use ($itemId) {
        AuthMiddleware::require('teacher', 'student');
        makeCommentController()->add($itemId);
    })(),

    $method === 'GET' && $roomId !== null && $segment3 === 'queue' && $itemId !== null && $segment5 === 'comments' => (function () use ($itemId) {
        AuthMiddleware::require('teacher', 'student');
        makeCommentController()->list($itemId);
    })(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room route not found.']);
    })()
};
