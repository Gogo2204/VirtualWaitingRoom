<?php

namespace App\Models;

class RoomItem extends Model
{
    protected string $table = 'room_items';

    public function joinQueue(
        int $roomId,
        int $studentId,
        int $position
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO room_items
            (
                room_id,
                student_id,
                position
            )
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $roomId,
            $studentId,
            $position
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(
        int $id,
        string $status
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE room_items
            SET status = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $status,
            $id
        ]);
    }

    public function getCurrentStudent(int $roomId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM room_items
            WHERE room_id = ?
            AND status = 'in_session'
            LIMIT 1
        ");

        $stmt->execute([$roomId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}