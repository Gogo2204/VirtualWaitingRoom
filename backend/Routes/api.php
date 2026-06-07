<?php

require_once __DIR__ . '/../../config/database.php';

use App\Helpers\JwtHelper;

JwtHelper::init();

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

header('Content-Type: application/json');

if (str_starts_with($path, '/api/auth')) {
    require_once __DIR__ . '/auth.php';
} elseif (str_starts_with($path, '/api/users')) {
    require_once __DIR__ . '/users.php';
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Route not found.']);
}