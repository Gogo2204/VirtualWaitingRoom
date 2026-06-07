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

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['queue_seconds' => 0, 'meeting_seconds' => 0];
    }

    public function getStatsForRoom(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS students_served,
                AVG(queue_secs) AS avg_queue_seconds,
                AVG(meeting_secs) AS avg_meeting_seconds
            FROM (
                SELECT
                    room_item_id,
                    TIMESTAMPDIFF(SECOND,
                        MAX(CASE WHEN event='joined'  THEN recorded_at END),
                        MAX(CASE WHEN event='invited' THEN recorded_at END)
                    ) AS queue_secs,
                    TIMESTAMPDIFF(SECOND,
                        MAX(CASE WHEN event='invited' THEN recorded_at END),
                        MAX(CASE WHEN event='done'    THEN recorded_at END)
                    ) AS meeting_secs
                FROM room_history
                WHERE room_id = ?
                GROUP BY room_item_id
                HAVING SUM(event = 'done') > 0
            ) t
        ");
        $stmt->execute([$roomId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $peakStmt = $this->db->prepare("
            SELECT HOUR(recorded_at) AS peak_hour, COUNT(*) AS cnt
            FROM room_history
            WHERE room_id = ? AND event = 'joined'
            GROUP BY HOUR(recorded_at)
            ORDER BY cnt DESC
            LIMIT 1
        ");
        $peakStmt->execute([$roomId]);
        $peak = $peakStmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'students_served'    => (int)($row['students_served'] ?? 0),
            'avg_queue_seconds'  => $row['avg_queue_seconds']  !== null ? (float)$row['avg_queue_seconds']  : null,
            'avg_meeting_seconds' => $row['avg_meeting_seconds'] !== null ? (float)$row['avg_meeting_seconds'] : null,
            'peak_hour'          => $peak ? (int)$peak['peak_hour'] : null,
        ];
    }

    public function getStatsForTeacherBySubject(int $teacherId, ?int $subjectId): array
    {
        $subjectFilter = $subjectId !== null ? 'AND s.id = ?' : '';
        $params = $subjectId !== null ? [$teacherId, $subjectId] : [$teacherId];

        $stmt = $this->db->prepare("
            SELECT
                s.id AS subject_id,
                s.type AS subject_type,
                COUNT(DISTINCT r.id) AS room_count,
                COUNT(DISTINCT done_items.room_item_id) AS students_served,
                AVG(t.queue_secs) AS avg_queue_seconds,
                AVG(t.meeting_secs) AS avg_meeting_seconds
            FROM subjects s
            JOIN rooms r ON r.subject_id = s.id AND r.teacher_id = ?
            LEFT JOIN (
                SELECT room_id, room_item_id
                FROM room_history
                WHERE event = 'done'
            ) done_items ON done_items.room_id = r.id
            LEFT JOIN (
                SELECT
                    room_item_id,
                    TIMESTAMPDIFF(SECOND,
                        MAX(CASE WHEN event='joined'  THEN recorded_at END),
                        MAX(CASE WHEN event='invited' THEN recorded_at END)
                    ) AS queue_secs,
                    TIMESTAMPDIFF(SECOND,
                        MAX(CASE WHEN event='invited' THEN recorded_at END),
                        MAX(CASE WHEN event='done'    THEN recorded_at END)
                    ) AS meeting_secs
                FROM room_history
                GROUP BY room_item_id
            ) t ON t.room_item_id = done_items.room_item_id
            {$subjectFilter}
            GROUP BY s.id, s.type
            ORDER BY s.type
        ");
        $stmt->execute($params);

        return array_map(fn($row) => [
            'subject_id'          => (int)$row['subject_id'],
            'subject_type'        => $row['subject_type'],
            'room_count'          => (int)$row['room_count'],
            'students_served'     => (int)$row['students_served'],
            'avg_queue_seconds'   => $row['avg_queue_seconds']   !== null ? (float)$row['avg_queue_seconds']   : null,
            'avg_meeting_seconds' => $row['avg_meeting_seconds'] !== null ? (float)$row['avg_meeting_seconds'] : null,
        ], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function getTimesForItems(array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

        $stmt = $this->db->prepare("
            SELECT
                room_item_id,
                TIMESTAMPDIFF(SECOND,
                    MAX(CASE WHEN event = 'joined'  THEN recorded_at END),
                    MAX(CASE WHEN event = 'invited' THEN recorded_at END)
                ) AS queue_seconds,
                TIMESTAMPDIFF(SECOND,
                    MAX(CASE WHEN event = 'invited' THEN recorded_at END),
                    MAX(CASE WHEN event = 'done'    THEN recorded_at END)
                ) AS meeting_seconds
            FROM room_history
            WHERE room_item_id IN ({$placeholders})
            GROUP BY room_item_id
        ");

        $stmt->execute($itemIds);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['room_item_id']] = [
                'queue_seconds'   => $row['queue_seconds']   ?? 0,
                'meeting_seconds' => $row['meeting_seconds'] ?? 0,
            ];
        }

        return $result;
    }
}
