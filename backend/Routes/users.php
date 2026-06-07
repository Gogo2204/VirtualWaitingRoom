<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\UserController;
use App\Services\UserService;
use App\Models\User;
use App\Models\TeacherStudent;

match (true) {
    $method === 'POST' && $path === '/api/users/teacher' => (function () {
        AuthMiddleware::require('admin');
        $controller = new UserController(new UserService(new User(getDb())));
        $controller->createTeacher();
    })(),

    $method === 'POST' && $path === '/api/users/import' => (function () {
        AuthMiddleware::require('teacher');
        $controller = new UserController(
            new UserService(
                new User(getDb()),
                new TeacherStudent(getDb())
            )
        );
        $controller->importStudents();
    })(),
    
    default => (function () {
      http_response_code(404);
      echo json_encode(['success' => false, 'message' => 'User route not found.']);
  })()
};