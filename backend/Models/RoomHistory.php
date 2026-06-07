<?php

namespace App\Models;

class RoomHistory extends Model
{
    protected string $table = 'room_history';

    public function record(int $roomItemId, int $studentId, int $roomId, string $event): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO room_history (room_item_id, student_id, room_id, event)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$roomItemId, $studentId, $roomId, $event]);
    }

    public function getTimes(int $roomItemId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                TIMESTAMPDIFF(SECOND,
                    MAX(CASE WHEN event = 'joined'  THEN recorded_at END),
                    MAX(CASE WHEN event = 'invited' THEN recorded_at END)
                ) AS queue_seconds,
                TIMESTAMPDIFF(SECOND,
                    MAX(CASE WHEN event = 'invited' THEN recorded_at END),
                    MAX(CASE WHEN event = 'done'    THEN recorded_at END)
                ) AS meeting_seconds
            FROM room_history
            WHERE room_item_id = ?
        ");

        $stmt->execute([$roomItemId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['queue_seconds' => null, 'meeting_seconds' => null];
    }
}
