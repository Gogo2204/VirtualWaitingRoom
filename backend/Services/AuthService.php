<?php

namespace App\Services;

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

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}