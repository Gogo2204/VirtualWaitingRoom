<?php

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

match (true) {
    $method === 'GET' && $path === '' => (function () {
        require_once __DIR__ . '/pages/home.php';
    })(),

    $method === 'GET' && $path === '/login' => (function () {
        require_once __DIR__ . '/pages/login.php';
    })(),

    $method === 'GET' && $path === '/register' => (function () {
        require_once __DIR__ . '/pages/register.php';
    })(),

    $method === 'GET' && $path === '/change-password' => (function () {
        require_once __DIR__ . '/pages/change-password.php';
    })(),

    $method === 'GET' && $path === '/dashboard' => (function () {
        require_once __DIR__ . '/pages/dashboard.php';
    })(),

    $method === 'GET' && $path === '/rooms' => (function () {
        require_once __DIR__ . '/pages/rooms.php';
    })(),

    $method === 'GET' && $path === '/rooms/create' => (function () {
        require_once __DIR__ . '/pages/create-room.php';
    })(),

    $method === 'GET' && preg_match('#^/rooms/(\d+)$#', $path, $m) => (function () use ($m) {
        $roomId = (int)$m[1];
        require_once __DIR__ . '/pages/room.php';
    })(),

    $method === 'GET' && $path === '/stats' => (function () {
        require_once __DIR__ . '/pages/stats.php';
    })(),

    default => (function () {
        http_response_code(404);
        require_once __DIR__ . '/pages/404.php';
    })()
};