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

    public function findByIdWithTeacher(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT r.*,
                   u.first_name AS teacher_first_name,
                   u.last_name  AS teacher_last_name,
                   u.profile_picture AS teacher_profile_picture
            FROM rooms r
            JOIN users u ON u.id = r.teacher_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getQueue(int $roomId): array
    {
        $stmt = $this->db->prepare("
            SELECT ri.*, u.first_name, u.last_name, u.profile_picture
            FROM room_items ri
            JOIN users u ON u.id = ri.student_id
            WHERE ri.room_id = ?
            ORDER BY ri.position
        ");

        $stmt->execute([$roomId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByTeacher(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, s.type AS subject_type
            FROM rooms r
            JOIN subjects s ON s.id = r.subject_id
            WHERE r.teacher_id = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByTeachersWithStatus(array $teacherIds, string $status): array
    {
        if (empty($teacherIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));

        $stmt = $this->db->prepare("
            SELECT r.*, s.type AS subject_type
            FROM rooms r
            JOIN subjects s ON s.id = r.subject_id
            WHERE r.teacher_id IN ({$placeholders})
            AND r.status = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([...$teacherIds, $status]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE rooms SET status = ? WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }
}