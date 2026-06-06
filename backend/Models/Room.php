<?php

namespace App\Models;

class Room extends Model
{
    protected string $table = 'rooms';

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO rooms
            (
                teacher_id,
                subject_id,
                name,
                description,
                wait_time_minutes,
                url
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['teacher_id'],
            $data['subject_id'],
            $data['name'],
            $data['description'] ?? "",
            $data['wait_time_minutes'] ?? 15,
            $data['url']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getQueue(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT ri.*, u.first_name, u.last_name
            FROM room_items ri
            JOIN users u ON u.id = ri.student_id
            WHERE ri.room_id = ?
            ORDER BY ri.position
        ");

        $stmt->execute([$roomId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}