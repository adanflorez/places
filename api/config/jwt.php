<?php
require_once __DIR__ . '/config.php';

class JWT {

    public static function generate(array $payload): string {
        $header  = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::base64url(json_encode(array_merge($payload, [
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY,
        ])));
        $signature = self::base64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

        return "$header.$payload.$signature";
    }

    public static function verify(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        $expected = self::base64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        if (!hash_equals($expected, $signature)) return null;

        $data = json_decode(self::base64urlDecode($payload), true);
        if (!$data || $data['exp'] < time()) return null;

        return $data;
    }

    private static function base64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
