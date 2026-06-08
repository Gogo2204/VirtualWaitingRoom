<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\TeacherStudent;
use App\Middleware\AuthMiddleware;

class AdminController
{
    public function __construct(
        private User $userModel,
        private TeacherStudent $tsModel
    ) {}

    public function listUsers(): void
    {
        echo json_encode(['success' => true, 'users' => $this->userModel->getAll()]);
    }

    public function deleteUser(): void
    {
        preg_match('#/api/admin/users/(\d+)$#', $_SERVER['REQUEST_URI'], $m);
        $id = (int)($m[1] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user id.']);
            return;
        }
        $current = AuthMiddleware::user()['sub'];
        if ($id === (int)$current) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account.']);
            return;
        }
        $this->userModel->delete($id);
        echo json_encode(['success' => true]);
    }

    public function listTeacherStudents(): void
    {
        echo json_encode(['success' => true, 'links' => $this->tsModel->getAllWithNames()]);
    }

    public function addTeacherStudent(): void
    {
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $teacherId = (int)($body['teacher_id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);
        if (!$teacherId || !$studentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'teacher_id and student_id required.']);
            return;
        }
        if (!$this->tsModel->isLinked($teacherId, $studentId)) {
            $this->tsModel->assign($teacherId, $studentId);
        }
        echo json_encode(['success' => true]);
    }

    public function removeTeacherStudent(): void
    {
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $teacherId = (int)($body['teacher_id'] ?? 0);
        $studentId = (int)($body['student_id'] ?? 0);
        if (!$teacherId || !$studentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'teacher_id and student_id required.']);
            return;
        }
        $this->tsModel->remove($teacherId, $studentId);
        echo json_encode(['success' => true]);
    }
}
