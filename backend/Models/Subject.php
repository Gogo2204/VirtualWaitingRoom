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
}