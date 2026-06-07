<?php

use App\Middleware\AuthMiddleware;
use App\Controllers\SubjectController;
use App\Models\Subject;

match (true) {
    $method === 'GET' && $path === '/api/subjects' => (function () {
        AuthMiddleware::require('teacher', 'student', 'admin');
        (new SubjectController(new Subject(getDb())))->list();
    })(),

    default => (function () {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Subject route not found.']);
    })()
};
