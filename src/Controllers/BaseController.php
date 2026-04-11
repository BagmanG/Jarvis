<?php
namespace MiniApp\Controllers;

use MiniApp\Core\Request;
use MiniApp\Repositories\UserRepository;
use MiniApp\Support\ApiResponse;
use MiniApp\Support\Token;

abstract class BaseController
{
    protected Request $request;
    protected UserRepository $users;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->users = new UserRepository();
    }

    protected function requireUser(): array
    {
        $payload = Token::verify($this->request->bearerToken());
        if (!$payload || empty($payload['uid'])) {
            ApiResponse::error('unauthorized', 'Требуется авторизация.', 401);
        }

        $user = $this->users->findById((int) $payload['uid']);
        if (!$user) {
            ApiResponse::error('unauthorized', 'Пользователь не найден.', 401);
        }

        return $user;
    }
}
