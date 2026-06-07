<?php

namespace App\Controllers;

use App\Services\UserService;

class UserController
{
    public function __construct(private UserService $userService) {}

    public function createTeacher(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        $firstName = trim($body['first_name'] ?? '');
        $lastName  = trim($body['last_name']  ?? '');
        $email     = trim($body['email']      ?? '');

        try {
            $result = $this->userService->createTeacher($firstName, $lastName, $email);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'user'    => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            error_log('RuntimeException: code=' . $e->getCode() . ' msg=' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $code = (int)$e->getCode();
            $code = ($code >= 400 && $code < 600) ? $code : 500;
            http_response_code($code);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}