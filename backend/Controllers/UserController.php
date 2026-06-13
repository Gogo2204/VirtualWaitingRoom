<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Middleware\AuthMiddleware;

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

    public function getProfile(): void
    {
        $user   = AuthMiddleware::user();
        $result = $this->userService->getProfile((int)$user['sub']);
        echo json_encode(['success' => true, 'user' => $result]);
    }

    public function updateProfile(): void
    {
        $user  = AuthMiddleware::user();
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $result = $this->userService->updateProfile(
                (int)$user['sub'],
                trim($body['first_name'] ?? ''),
                trim($body['last_name']  ?? ''),
                trim($body['email']      ?? '')
            );
            echo json_encode(['success' => true, 'user' => $result]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function uploadAvatar(): void
    {
        $user = AuthMiddleware::user();

        if (empty($_FILES['avatar'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
            return;
        }

        try {
            $result = $this->userService->uploadAvatar((int)$user['sub'], $_FILES['avatar']);
            echo json_encode(['success' => true, 'user' => $result]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function listStudents(): void
    {
        $teacherId = (int)AuthMiddleware::user()['sub'];
        $students  = $this->userService->listStudents($teacherId);
        echo json_encode(['success' => true, 'students' => $students]);
    }

    public function importStudents(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!empty($body['students']) && is_array($body['students'])) {
            $students = $body['students'];
        } elseif (!empty($body['faculty_numbers']) && is_array($body['faculty_numbers'])) {
            $students = $body['faculty_numbers'];
        } else {
            $students = [];
        }

        if (empty($students)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No students provided.']);
            return;
        }

        $teacherId = AuthMiddleware::user()['sub'];

        try {
            $result = $this->userService->importStudents($students, $teacherId);
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
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