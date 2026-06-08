<?php

namespace App\Models;

class TeacherStudent extends Model
{
    protected string $table = 'teacher_student';

    public function assign(int $teacherId, int $studentId): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO teacher_student
            (teacher_id, student_id)
            VALUES (?, ?)
        ");

        return $stmt->execute([
            $teacherId,
            $studentId
        ]);
    }

    public function isLinked(int $teacherId, int $studentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM teacher_student
            WHERE teacher_id = ? AND student_id = ?
            LIMIT 1
        ");

        $stmt->execute([$teacherId, $studentId]);

        return (bool)$stmt->fetchColumn();
    }

    public function importBatch(int $teacherId, array $studentIds): void
    {
        foreach ($studentIds as $studentId) {
            if (!$this->isLinked($teacherId, $studentId)) {
                $this->assign($teacherId, $studentId);
            }
        }
    }

    public function getStudentIds(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT student_id FROM teacher_student
            WHERE teacher_id = ?
        ");

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getTeacherIds(int $studentId): array
    {
        $stmt = $this->db->prepare("
            SELECT teacher_id FROM teacher_student
            WHERE student_id = ?
        ");

        $stmt->execute([$studentId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getAllWithNames(): array
    {
        $stmt = $this->db->query("
            SELECT ts.teacher_id, ts.student_id,
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   t.email AS teacher_email,
                   CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                   s.faculty_number
            FROM teacher_student ts
            JOIN users t ON t.id = ts.teacher_id
            JOIN users s ON s.id = ts.student_id
            ORDER BY t.last_name, t.first_name, s.last_name, s.first_name
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function remove(int $teacherId, int $studentId): void
    {
        $stmt = $this->db->prepare("DELETE FROM teacher_student WHERE teacher_id = ? AND student_id = ?");
        $stmt->execute([$teacherId, $studentId]);
    }
}