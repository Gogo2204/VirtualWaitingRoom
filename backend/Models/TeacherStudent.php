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
}