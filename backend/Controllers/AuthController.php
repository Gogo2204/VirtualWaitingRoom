<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $authService) {}

    public function login(): void
    {
        $body     = json_decode(file_get_contents('php://input'), true);
        $email    = trim($body['email']    ?? '');
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
            http_response_code((int)($e->getCode() ?: 500));
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function register(): void
    {
        $body           = json_decode(file_get_contents('php://input'), true) ?? [];
        $firstName      = trim($body['first_name']      ?? '');
        $lastName       = trim($body['last_name']       ?? '');
        $email          = trim($body['email']           ?? '');
        $password       = trim($body['password']        ?? '');
        $facultyNumber  = trim($body['faculty_number']  ?? '');

        try {
            $result = $this->authService->register(
                $firstName,
                $lastName,
                $email,
                $password,
                $facultyNumber
            );

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'token'   => $result['token'],
                'user'    => $result['user'],
            ]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            $code = ($code >= 400 && $code < 600) ? $code : 500;
            http_response_code($code);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function changePassword(): void 
    {
        $body        = json_decode(file_get_contents('php://input'), true);
        $oldPassword = trim($body['old_password']    ?? '');
        $newPassword = trim($body['new_password'] ?? '');

        try {
            $result = $this->authService->changePassword(
                $oldPassword,
                $newPassword
            );

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
            $code = (int)$e->getCode();
            $code = ($code >= 400 && $code < 600) ? $code : 500;
            http_response_code($code);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}