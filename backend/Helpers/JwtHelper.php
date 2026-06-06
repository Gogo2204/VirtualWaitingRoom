<?php

namespace App\Helpers;

class JwtHelper
{
    private static string $secret;

    public static function init(): void
    {
        self::$secret = $_ENV['JWT_SECRET'] ?? throw new \RuntimeException('JWT_SECRET not set');
    }

    public static function encode(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (8 * 3600);

        $header  = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body    = self::base64url(json_encode($payload));
        $sig     = self::base64url(hash_hmac('sha256', "$header.$body", self::$secret, true));

        return "$header.$body.$sig";
    }

    public static function decode(string $token): array
    {
        [$header, $body, $sig] = explode('.', $token) + [null, null, null];

        $expected = self::base64url(hash_hmac('sha256', "$header.$body", self::$secret, true));

        if (!hash_equals($expected, $sig)) {
            throw new \RuntimeException('Invalid token signature.', 401);
        }

        $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);

        if ($payload['exp'] < time()) {
            throw new \RuntimeException('Token expired.', 401);
        }

        return $payload;
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}