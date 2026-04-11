<?php
namespace MiniApp\Support;

class Token
{
    public static function issue(int $userId, int $ttlSeconds = 2592000): string
    {
        $payload = [
            'uid' => $userId,
            'exp' => time() + $ttlSeconds,
            'iat' => time(),
        ];

        $json = json_encode($payload);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, config('app.token_secret'));

        return $encoded . '.' . $signature;
    }

    public static function verify(?string $token): ?array
    {
        if (!$token || strpos($token, '.') === false) {
            return null;
        }

        list($encoded, $signature) = explode('.', $token, 2);
        $expected = hash_hmac('sha256', $encoded, config('app.token_secret'));

        if (!hash_equals($expected, (string) $signature)) {
            return null;
        }

        $json = base64_decode(strtr($encoded, '-_', '+/'));
        $payload = json_decode($json ?: '{}', true);

        if (!is_array($payload) || empty($payload['uid']) || empty($payload['exp'])) {
            return null;
        }

        if ((int) $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}
