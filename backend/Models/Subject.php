<?php

namespace App\Models;

class Subject extends Model
{
    protected string $table = 'subjects';

    public function create(string $type): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO subjects (type)
            VALUES (?)
        ");

        $stmt->execute([$type]);

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM subjects WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM subjects ORDER BY type ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function isInUse(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rooms WHERE subject_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}