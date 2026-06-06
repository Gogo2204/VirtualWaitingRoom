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
}