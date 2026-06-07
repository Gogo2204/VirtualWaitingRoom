<?php

namespace App\Models;

class Comment extends Model
{
    protected string $table = 'comments';

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO comments
            (
                room_item_id,
                user_id,
                visibility,
                content
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['room_item_id'],
            $data['user_id'],
            $data['visibility'],
            $data['content']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getForRoomItem(int $roomItemId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.first_name, u.last_name
            FROM comments c
            JOIN users u
                ON u.id = c.user_id
            WHERE c.room_item_id = ?
            ORDER BY c.created_at
        ");

        $stmt->execute([$roomItemId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}