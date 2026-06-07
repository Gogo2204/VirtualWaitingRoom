<?php

namespace App\Middleware;

use App\Helpers\JwtHelper;

class AuthMiddleware
{
    private static ?array $currentUser = null;

    public static function handle(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            self::abort(401, 'Missing or malformed Authorization header.');
        }

        $token = substr($header, 7);

        try {
            $payload = JwtHelper::decode($token);
        } catch (\RuntimeException $e) {
            self::abort($e->getCode() ?: 401, $e->getMessage());
        }

        self::$currentUser = $payload;
    }

    public static function user(): ?array
    {
        return self::$currentUser;
    }

    public static function require(string ...$roles): void
    {
        self::handle();

        if (empty($roles)) return;

        $userRole = self::$currentUser['role'] ?? '';

        if (!in_array($userRole, $roles, true)) {
            self::abort(403, 'Insufficient permissions.');
        }
    }

    private static function abort(int $code, string $message): never
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}