<?php

namespace App\Services;

use App\Middleware\AuthMiddleware;

use App\Models\User;
use App\Helpers\JwtHelper;

class AuthService
{
    public function __construct(private User $userModel) {}

    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            throw new \InvalidArgumentException('Email and password are required.');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new \RuntimeException('Invalid credentials.', 401);
        }

        if ($user['status'] !== 'registered') {
            throw new \RuntimeException('Account is not active.', 403);
        }

        $token = JwtHelper::encode([
            'sub'  => $user['id'],
            'role' => $user['role'],
        ]);

        unset($user['password_hash']);

        return ['token' => $token, 'user' => $user];
    }

    public function register(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $facultyNumber
    ): array {
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($facultyNumber)) {
            throw new \InvalidArgumentException('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }

        $existing = $this->userModel->findByFacultyNumber($facultyNumber);

        if (!$existing) {
            throw new \RuntimeException('Faculty number not found or not eligible for registration.', 403);
        }

        if ($existing['status'] !== 'imported') {
            throw new \RuntimeException('This faculty number is already registered.', 409);
        }

        $this->userModel->update($existing['id'], [
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'status'        => 'registered'
        ]);

        $user = $this->userModel->findById($existing['id']);
        unset($user['password_hash']);

        $token = JwtHelper::encode([
            'sub'  => $user['id'],
            'role' => $user['role'],
        ]);

        return ['token' => $token, 'user' => $user];
    }

    public function changePassword(
        string $oldPassword,
        string $newPassword
    ): array {

        if (empty($oldPassword) || empty($newPassword)) {
            throw new \InvalidArgumentException('All fields are required.');
        }

        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }
        
        $userId = AuthMiddleware::user()['sub'] ?? null;
        if (!$userId) {
            throw new \RuntimeException('User not authenticated.', 401);
        }
        $user = $this->userModel->findById($userId);

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            throw new \RuntimeException('Invalid credentials.', 401);
        }

        if ($user['status'] !== 'registered') {
            throw new \RuntimeException('This account is not active.', 409);
        }

        $this->userModel->update($user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)
        ]);

        $user = $this->userModel->findById($user['id']);
        unset($user['password_hash']);

        $token = JwtHelper::encode([
            'sub'  => $user['id'],
            'role' => $user['role'],
        ]);

        return ['token' => $token, 'user' => $user];
    }
}