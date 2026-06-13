<?php

namespace App\Models;

class Grade extends Model
{
    protected string $table = 'grades';

    public function upsert(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO grades (room_item_id, student_id, room_id, grade)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                grade         = VALUES(grade),
                room_item_id  = VALUES(room_item_id),
                recorded_at   = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            $data['room_item_id'],
            $data['student_id'],
            $data['room_id'],
            $data['grade'],
        ]);

        if ($this->db->lastInsertId()) {
            return (int)$this->db->lastInsertId();
        }

        $find = $this->db->prepare("
            SELECT id FROM grades WHERE student_id = ? AND room_id = ?
        ");
        $find->execute([$data['student_id'], $data['room_id']]);
        return (int)$find->fetchColumn();
    }

    public function findByRoom(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT g.*, u.first_name, u.last_name, u.faculty_number
            FROM grades g
            JOIN users u ON u.id = g.student_id
            WHERE g.room_id = ?
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByStudent(int $studentId): array
    {
        $stmt = $this->db->prepare("
            SELECT g.*, r.name AS room_name, s.type AS subject_type
            FROM grades g
            JOIN rooms r ON r.id = g.room_id
            JOIN subjects s ON s.id = r.subject_id
            WHERE g.student_id = ?
            ORDER BY g.recorded_at DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByRoomAndStudent(int $roomId, int $studentId): array|false
    {
        $stmt = $this->db->prepare("
            SELECT g.*, u.first_name, u.last_name
            FROM grades g
            JOIN users u ON u.id = g.student_id
            WHERE g.room_id = ? AND g.student_id = ?
        ");
        $stmt->execute([$roomId, $studentId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteByRoomAndStudent(int $roomId, int $studentId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM grades WHERE room_id = ? AND student_id = ?
        ");
        $stmt->execute([$roomId, $studentId]);
        return $stmt->rowCount() > 0;
    }
}