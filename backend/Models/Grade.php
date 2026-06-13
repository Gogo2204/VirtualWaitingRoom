<?php

namespace App\Models;

class Grade extends Model
{
    protected string $table = 'grades';

    public function create(int $roomItemId, int $studentId, int $roomId, float $grade): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO grades (room_item_id, student_id, room_id, grade)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$roomItemId, $studentId, $roomId, $grade]);

        return (int)$this->db->lastInsertId();
    }

    public function updateGrade(int $studentId, int $roomId, float $grade): bool
    {
        $stmt = $this->db->prepare("
            UPDATE grades
            SET grade = ?, recorded_at = CURRENT_TIMESTAMP
            WHERE student_id = ? AND room_id = ?
        ");
        return $stmt->execute([$grade, $studentId, $roomId]);
    }

    public function findByStudentAndRoom(int $studentId, int $roomId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM grades
            WHERE student_id = ? AND room_id = ?
        ");
        $stmt->execute([$studentId, $roomId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getByRoom(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT g.*, u.first_name, u.last_name
            FROM grades g
            JOIN users u ON u.id = g.student_id
            WHERE g.room_id = ?
            ORDER BY g.recorded_at DESC
        ");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByStudent(int $studentId): array
    {
        $stmt = $this->db->prepare("
            SELECT g.*, r.name AS room_name
            FROM grades g
            JOIN rooms r ON r.id = g.room_id
            WHERE g.student_id = ?
            ORDER BY g.recorded_at DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
