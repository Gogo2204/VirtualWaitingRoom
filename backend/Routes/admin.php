<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\AdminController;
use App\Models\User;
use App\Models\TeacherStudent;
use App\Models\Subject;
use App\Models\Comment;

AuthMiddleware::require('admin');

$db         = getDb();
$controller = new AdminController(
    $db,
    new User($db),
    new TeacherStudent($db),
    new Subject($db),
    new Comment($db)
);

match (true) {
    $method === 'GET'    && $path === '/api/admin/stats'                                => $controller->getStats(),
    $method === 'GET'    && $path === '/api/admin/users'                                => $controller->listUsers(),
    $method === 'DELETE' && preg_match('#^/api/admin/users/\d+$#', $path)               => $controller->deleteUser(),
    $method === 'GET'    && $path === '/api/admin/teacher-student'                      => $controller->listTeacherStudents(),
    $method === 'POST'   && $path === '/api/admin/teacher-student'                      => $controller->addTeacherStudent(),
    $method === 'DELETE' && $path === '/api/admin/teacher-student'                      => $controller->removeTeacherStudent(),
    $method === 'GET'    && $path === '/api/admin/subjects'                             => $controller->listSubjects(),
    $method === 'POST'   && $path === '/api/admin/subjects'                             => $controller->createSubject(),
    $method === 'DELETE' && preg_match('#^/api/admin/subjects/\d+$#', $path)            => $controller->deleteSubject(),
    $method === 'GET'    && $path === '/api/admin/comments'                             => $controller->listComments(),
    $method === 'DELETE' && preg_match('#^/api/admin/comments/\d+$#', $path)            => $controller->deleteComment(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Admin route not found.']);
    })()
};
