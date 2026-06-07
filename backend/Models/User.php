<?php

namespace App\Models;

class User extends Model
{
    protected string $table = 'users';

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users
            (
                first_name,
                last_name,
                email,
                password_hash,
                faculty_number,
                role,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['first_name'] ?? "",
            $data['last_name'] ?? "",
            $data['email'] ?? "",
            $data['password_hash'] ?? "",
            $data['faculty_number'] ?? null,
            $data['role'],
            $data['status']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getStudents(int $teacherId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.*
            FROM users u
            JOIN teacher_student ts
                ON ts.student_id = u.id
            WHERE ts.teacher_id = ?
        ");

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByFacultyNumber(string $facultyNumber): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE faculty_number = ? LIMIT 1"
        );
        $stmt->execute([$facultyNumber]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): void
    {
        $fields = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $stmt   = $this->db->prepare("UPDATE users SET $fields WHERE id = ?");
        $stmt->execute([...array_values($data), $id]);
    }
}