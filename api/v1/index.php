<?php
require_once dirname(__DIR__, 2) . '/src/Support/helpers.php';
require_once dirname(__DIR__, 2) . '/src/Support/Autoload.php';

use MiniApp\Controllers\AuthController;
use MiniApp\Controllers\BootstrapController;
use MiniApp\Controllers\CalendarController;
use MiniApp\Controllers\ProfileController;
use MiniApp\Controllers\SettingsController;
use MiniApp\Controllers\TaskController;
use MiniApp\Core\Request;
use MiniApp\Support\ApiResponse;
use MiniApp\Support\Logger;

if (!headers_sent()) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    $allowed = (array) config('cors.allowed_origins', ['*']);
    $allowOriginHeader = in_array('*', $allowed, true) ? '*' : (in_array($origin, $allowed, true) ? $origin : $allowed[0]);
    header('Access-Control-Allow-Origin: ' . $allowOriginHeader);
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Credentials: false');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set(config('app.timezone', 'UTC'));

$request = new Request();
$method = $request->method();
$path = $request->path();

try {
    if ($method === 'POST' && $path === '/auth/telegram') {
        (new AuthController($request))->telegram();
    }

    if ($method === 'GET' && $path === '/bootstrap') {
        (new BootstrapController($request))->get();
    }

    if ($method === 'GET' && $path === '/profile') {
        (new ProfileController($request))->show();
    }

    if ($method === 'PATCH' && $path === '/profile') {
        (new ProfileController($request))->update();
    }

    if ($method === 'POST' && $path === '/profile/avatar') {
        (new ProfileController($request))->uploadAvatar();
    }

    if ($method === 'DELETE' && $path === '/profile/avatar') {
        (new ProfileController($request))->deleteAvatar();
    }

    if ($method === 'GET' && $path === '/settings') {
        (new SettingsController($request))->show();
    }

    if ($method === 'PATCH' && $path === '/settings/theme') {
        (new SettingsController($request))->theme();
    }

    if ($method === 'GET' && $path === '/calendar/month') {
        (new CalendarController($request))->month();
    }

    if ($method === 'GET' && $path === '/tasks') {
        (new TaskController($request))->list();
    }

    if ($method === 'GET' && $path === '/tasks/range') {
        (new TaskController($request))->range();
    }

    if ($method === 'POST' && $path === '/tasks') {
        (new TaskController($request))->create();
    }

    if (preg_match('#^/tasks/(\d+)$#', $path, $matches)) {
        $taskId = (int) $matches[1];
        if ($method === 'GET') {
            (new TaskController($request))->show($taskId);
        }
        if ($method === 'PATCH') {
            (new TaskController($request))->update($taskId);
        }
        if ($method === 'DELETE') {
            (new TaskController($request))->delete($taskId);
        }
    }

    if ($method === 'PATCH' && preg_match('#^/tasks/(\d+)/status$#', $path, $matches)) {
        (new TaskController($request))->updateStatus((int) $matches[1]);
    }

    ApiResponse::error('not_found', 'Endpoint не найден.', 404);
} catch (Throwable $e) {
    Logger::api('error', $e->getMessage(), [
        'path' => $path,
        'method' => $method,
        'trace' => config('app.debug') ? $e->getTraceAsString() : null,
    ]);

    ApiResponse::error('server_error', config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера.', 500);
}
