<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\UserController;
use App\Services\UserService;
use App\Models\User;

AuthMiddleware::require('admin');

match (true) {
    $method === 'POST' && $path === '/api/users/teacher' => (function () {
        $controller = new UserController(new UserService(new User(getDb())));
        $controller->createTeacher();
    })(),

    default => (function () {
      http_response_code(404);
      echo json_encode(['success' => false, 'message' => 'User route not found.']);
  })()
};