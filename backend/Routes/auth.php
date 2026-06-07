<?php

use App\Middleware\AuthMiddleware;

use App\Controllers\AuthController;
use App\Services\AuthService;
use App\Models\User;

match (true) {
    $method === 'POST' && $path === '/api/auth/login' => (function () {
        $authController = new AuthController(new AuthService(new User(getDb())));
        $authController->login();
    })(),

    $method === 'POST' && $path === '/api/auth/register' => (function () {
        $authController = new AuthController(new AuthService(new User(getDb())));
        $authController->register();
    })(),

    $method === 'POST' && $path === '/api/auth/change-password' => (function () {
        AuthMiddleware::require('student', 'teacher', 'admin');
        $authController = new AuthController(new AuthService(new User(getDb())));
        $authController->changePassword();
    })(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Auth route not found.']);
    })()
};