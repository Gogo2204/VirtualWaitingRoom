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

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.content, c.visibility, c.created_at,
                   u.first_name, u.last_name,
                   r.id AS room_id, r.name AS room_name
            FROM comments c
            JOIN users u ON u.id = c.user_id
            JOIN room_items ri ON ri.id = c.room_item_id
            JOIN rooms r ON r.id = ri.room_id
            ORDER BY c.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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