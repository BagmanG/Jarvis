<?php
namespace MiniApp\Support;

class TelegramAuth
{
    public static function verifyInitData(string $initData): ?array
    {
        $botToken = (string) config('telegram.bot_token');
        if (!$botToken || $botToken === 'PASTE_BOT_TOKEN_HERE') {
            return null;
        }

        parse_str($initData, $data);
        if (empty($data['hash'])) {
            return null;
        }

        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);
        $checkStringParts = [];
        foreach ($data as $key => $value) {
            $checkStringParts[] = $key . '=' . $value;
        }

        $checkString = implode("\n", $checkStringParts);
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

        if (!hash_equals($calculatedHash, $hash)) {
            return null;
        }

        if (!empty($data['auth_date']) && (time() - (int) $data['auth_date']) > 86400) {
            return null;
        }

        $user = [];
        if (!empty($data['user'])) {
            $decodedUser = json_decode($data['user'], true);
            if (is_array($decodedUser)) {
                $user = $decodedUser;
            }
        }

        return [
            'auth_date' => isset($data['auth_date']) ? (int) $data['auth_date'] : null,
            'query_id' => $data['query_id'] ?? null,
            'user' => $user,
        ];
    }
}
