<?php
namespace MiniApp\Support;

use MiniApp\Core\Response;

class ApiResponse
{
    public static function success($data = [], array $meta = [], int $status = 200): void
    {
        Response::json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => (object) $meta,
        ], $status);
    }

    public static function error(string $code, string $message, int $status = 400, array $details = []): void
    {
        Response::json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
            'meta' => new \stdClass(),
        ], $status);
    }
}
