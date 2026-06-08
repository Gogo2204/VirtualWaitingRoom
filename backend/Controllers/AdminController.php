<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\TeacherStudent;
use App\Models\Subject;
use App\Models\Comment;
use App\Middleware\AuthMiddleware;

class AdminController
{
    public function __construct(
        private \PDO $db,
        private User $userModel,
        private TeacherStudent $tsModel,
        private Subject $subjectModel,
        private Comment $commentModel,
    ) {}

    /* ── Stats ───────────────────────────────────────────────────── */
    public function getStats(): void
    {
        $usersByRole   = $this->db->query("SELECT role, COUNT(*) FROM users GROUP BY role")
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
        $roomsByStatus = $this->db->query("SELECT status, COUNT(*) FROM rooms GROUP BY status")
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
        $activeQueue   = (int)$this->db->query(
            "SELECT COUNT(*) FROM room_items WHERE status IN ('waiting','invited_temp','invited_perm')"
        )->fetchColumn();
        $totalComments = (int)$this->db->query("SELECT COUNT(*) FROM comments")->fetchColumn();

        echo json_encode(['success' => true, 'stats' => [
            'users_by_role'   => $usersByRole,
            'rooms_by_status' => $roomsByStatus,
            'active_queue'    => $activeQueue,
            'total_comments'  => $totalComments,
        ]]);
    }

    /* ── Users ───────────────────────────────────────────────────── */
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
        if ($id === (int)AuthMiddleware::user()['sub']) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account.']);
            return;
        }
        $this->userModel->delete($id);
        echo json_encode(['success' => true]);
    }

    /* ── Teacher–Student ─────────────────────────────────────────── */
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

    /* ── Subjects ────────────────────────────────────────────────── */
    public function listSubjects(): void
    {
        echo json_encode(['success' => true, 'subjects' => $this->subjectModel->getAll()]);
    }

    public function createSubject(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $type = trim($body['type'] ?? '');
        if ($type === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Subject name is required.']);
            return;
        }
        $id = $this->subjectModel->create($type);
        echo json_encode(['success' => true, 'subject' => $this->subjectModel->findById($id)]);
    }

    public function deleteSubject(): void
    {
        preg_match('#/api/admin/subjects/(\d+)$#', $_SERVER['REQUEST_URI'], $m);
        $id = (int)($m[1] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid subject id.']);
            return;
        }
        if ($this->subjectModel->isInUse($id)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Cannot delete a subject that is used by rooms.']);
            return;
        }
        $this->subjectModel->delete($id);
        echo json_encode(['success' => true]);
    }

    /* ── Comments ────────────────────────────────────────────────── */
    public function listComments(): void
    {
        echo json_encode(['success' => true, 'comments' => $this->commentModel->getAll(100)]);
    }

    public function deleteComment(): void
    {
        preg_match('#/api/admin/comments/(\d+)$#', $_SERVER['REQUEST_URI'], $m);
        $id = (int)($m[1] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid comment id.']);
            return;
        }
        $this->commentModel->delete($id);
        echo json_encode(['success' => true]);
    }
}
