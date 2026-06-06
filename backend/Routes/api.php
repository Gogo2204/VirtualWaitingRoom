<?php

require_once __DIR__ . '/../../config/database.php';

use App\Controllers\AuthController;
use App\Services\AuthService;
use App\Models\User;
use App\Helpers\JwtHelper;

JwtHelper::init();

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = rtrim($path, '/');

match (true) {
    $method === 'GET' && $path === '/login' => (function () {
        require_once __DIR__ . '/../../frontend/pages/login.php';
    })(),

    $method === 'POST' && $path === '/api/auth/login' => (function () {
        header('Content-Type: application/json');
        $userModel      = new User(getDb());
        $authService    = new AuthService($userModel);
        $authController = new AuthController($authService);
        $authController->login();
    })(),

    default => (function () {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
    })()
};