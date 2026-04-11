<?php
namespace MiniApp\Controllers;

use MiniApp\Core\Request;
use MiniApp\Repositories\SettingsRepository;
use MiniApp\Repositories\UserRepository;
use MiniApp\Services\AuthService;
use MiniApp\Support\ApiResponse;
use MiniApp\Support\TelegramAuth;

class AuthController
{
    private Request $request;
    private UserRepository $users;
    private SettingsRepository $settings;
    private AuthService $authService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->users = new UserRepository();
        $this->settings = new SettingsRepository();
        $this->authService = new AuthService();
    }

    public function telegram(): void
    {
        $initData = (string) $this->request->input('initData', '');
        $selectedDate = (string) $this->request->input('selected_date', date('Y-m-d'));
        $unsafeUser = $this->request->input('user', []);

        $verified = null;
        if ($initData) {
            $verified = TelegramAuth::verifyInitData($initData);
        }

        if (!$verified && !empty($unsafeUser['id']) && config('app.debug') === true) {
            $verified = ['user' => $unsafeUser];
        }

        if (!$verified || empty($verified['user']['id'])) {
            ApiResponse::error('telegram_auth_failed', 'Не удалось подтвердить пользователя Telegram. Проверьте bot token и initData.', 401);
        }

        $user = $this->users->createOrUpdateFromTelegram($verified['user']);
        $settings = $this->settings->getByUserId((int) $user['id']);
        $bootstrap = $this->authService->bootstrapForUser($user, $settings, $selectedDate);

        ApiResponse::success($bootstrap);
    }
}
