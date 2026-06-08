<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\AdminController;
use App\Models\User;
use App\Models\TeacherStudent;

AuthMiddleware::require('admin');

$controller = new AdminController(new User(getDb()), new TeacherStudent(getDb()));

match (true) {
    $method === 'GET'    && $path === '/api/admin/users'                          => $controller->listUsers(),
    $method === 'DELETE' && preg_match('#^/api/admin/users/\d+$#', $path)        => $controller->deleteUser(),
    $method === 'GET'    && $path === '/api/admin/teacher-student'                => $controller->listTeacherStudents(),
    $method === 'POST'   && $path === '/api/admin/teacher-student'                => $controller->addTeacherStudent(),
    $method === 'DELETE' && $path === '/api/admin/teacher-student'                => $controller->removeTeacherStudent(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Admin route not found.']);
    })()
};
