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

    public function setEta(int $id, string $datetime): void
    {
        $stmt = $this->db->prepare("
            UPDATE room_items SET eta = ? WHERE id = ?
        ");

        $stmt->execute([$datetime, $id]);
    }

    public function getStatusCounts(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) AS cnt
            FROM room_items
            WHERE room_id = ?
            GROUP BY status
        ");
        $stmt->execute([$roomId]);
        $counts = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
        }
        return $counts;
    }

    public function addMinutesToEta(int $roomId, int $minutes): void
    {
        $stmt = $this->db->prepare("
            UPDATE room_items
            SET eta = DATE_ADD(eta, INTERVAL ? MINUTE)
            WHERE room_id = ? AND status = 'waiting' AND eta IS NOT NULL AND eta > NOW()
        ");
        $stmt->execute([$minutes, $roomId]);
    }

    public function updateStatusBulk(int $roomId, string $fromStatus, string $toStatus): int
    {
        $stmt = $this->db->prepare("
            UPDATE room_items SET status = ? WHERE room_id = ? AND status = ?
        ");
        $stmt->execute([$toStatus, $roomId, $fromStatus]);
        return $stmt->rowCount();
    }

    public function getWaiting(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, position FROM room_items WHERE room_id = ? AND status = 'waiting' ORDER BY position ASC
        ");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function recalcEtasFromTime(int $roomId, int $waitTimeMinutes, string $startDatetime): void
    {
        $rows   = $this->getWaiting($roomId);
        $update = $this->db->prepare("UPDATE room_items SET eta = ? WHERE id = ?");
        $baseTs = strtotime($startDatetime);
        foreach ($rows as $i => $row) {
            $eta = date('Y-m-d H:i:s', $baseTs + ($i * $waitTimeMinutes * 60));
            $update->execute([$eta, $row['id']]);
        }
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