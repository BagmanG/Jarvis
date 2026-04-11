<?php
namespace MiniApp\Controllers;

use MiniApp\Repositories\SettingsRepository;
use MiniApp\Support\ApiResponse;
use MiniApp\Support\Validator;

class SettingsController extends BaseController
{
    public function show(): void
    {
        $user = $this->requireUser();
        $settings = (new SettingsRepository())->getByUserId((int) $user['id']);
        ApiResponse::success($settings);
    }

    public function theme(): void
    {
        $user = $this->requireUser();
        list($errors, $data) = Validator::validateTheme($this->request->input());
        if ($errors) {
            ApiResponse::error('validation_error', 'Проверьте настройки темы.', 422, $errors);
        }

        $settings = (new SettingsRepository())->updateTheme((int) $user['id'], $data);
        ApiResponse::success($settings);
    }
}
