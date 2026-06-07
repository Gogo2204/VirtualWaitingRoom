<?php

use App\Controllers\AuthController;
use App\Services\AuthService;
use App\Models\User;

match (true) {
    $method === 'POST' && $path === '/api/auth/login' => (function () {
        $authController = new AuthController(new AuthService(new User(getDb())));
        $authController->login();
    })(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Auth route not found.']);
    })()
};