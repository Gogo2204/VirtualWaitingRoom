<?php

namespace App\Controllers;

use App\Services\GradeService;
use App\Middleware\AuthMiddleware;

class GradeController
{
    public function __construct(private GradeService $gradeService) {}

    public function setGrade(int $roomId): void
    {
        $user = AuthMiddleware::user();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $grade = $this->gradeService->setGrade((int)$user['sub'], $roomId, $body);
            http_response_code(200);
            echo json_encode(['success' => true, 'grade' => $grade]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getRoomGrades(int $roomId): void
    {
        $user = AuthMiddleware::user();

        try {
            $grades = $this->gradeService->getGradesForRoom((int)$user['sub'], $roomId);
            echo json_encode(['success' => true, 'grades' => $grades]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getMyGrades(): void
    {
        $user   = AuthMiddleware::user();
        $grades = $this->gradeService->getGradesForStudent((int)$user['sub']);
        echo json_encode(['success' => true, 'grades' => $grades]);
    }

    public function deleteGrade(int $roomId, int $studentId): void
    {
        $user = AuthMiddleware::user();

        try {
            $this->gradeService->deleteGrade((int)$user['sub'], $roomId, $studentId);
            echo json_encode(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}