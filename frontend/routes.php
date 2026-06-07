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

    $method === 'GET' && $path === '/dashboard' => (function () {
        require_once __DIR__ . '/pages/dashboard.php';
    })(),

    default => (function () {
        http_response_code(404);
        require_once __DIR__ . '/pages/404.php';
    })()
};