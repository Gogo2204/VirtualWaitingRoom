<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $authService) {}

    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        $email    = trim($body['email'] ?? '');
        $password = trim($body['password'] ?? '');

        try {
            $result = $this->authService->login($email, $password);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'token'   => $result['token'],
                'user'    => $result['user'],
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}