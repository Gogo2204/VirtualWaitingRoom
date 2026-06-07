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

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getByStudentAndRoom(int $studentId, int $roomId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM room_items
            WHERE student_id = ? AND room_id = ?
            LIMIT 1
        ");

        $stmt->execute([$studentId, $roomId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getNextWaiting(int $roomId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM room_items
            WHERE room_id = ? AND status = 'waiting'
            ORDER BY position ASC
            LIMIT 1
        ");

        $stmt->execute([$roomId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function reorderAfterRemoval(int $roomId, int $removedPosition): void
    {
        $stmt = $this->db->prepare("
            UPDATE room_items
            SET position = position - 1
            WHERE room_id = ? AND position > ? AND status = 'waiting'
        ");

        $stmt->execute([$roomId, $removedPosition]);
    }

    public function setEta(int $id, string $datetime): bool
    {
        $stmt = $this->db->prepare("
            UPDATE room_items SET eta = ? WHERE id = ?
        ");

        return $stmt->execute([$datetime, $id]);
    }

    public function recalcEtas(int $roomId, int $waitTimeMinutes): void
    {
        $stmt = $this->db->prepare("
            SELECT id, position
            FROM room_items
            WHERE room_id = ? AND status = 'waiting'
            ORDER BY position ASC
        ");

        $stmt->execute([$roomId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $update = $this->db->prepare("
            UPDATE room_items
            SET eta = DATE_ADD(NOW(), INTERVAL ? MINUTE)
            WHERE id = ?
        ");

        foreach ($rows as $i => $row) {
            $minutesFromNow = ($i + 1) * $waitTimeMinutes;
            $update->execute([$minutesFromNow, $row['id']]);
        }
    }
}