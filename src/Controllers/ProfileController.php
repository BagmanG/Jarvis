<?php
namespace MiniApp\Controllers;

use MiniApp\Repositories\FileRepository;
use MiniApp\Services\AvatarService;
use MiniApp\Support\ApiResponse;

class ProfileController extends BaseController
{
    public function show(): void
    {
        $user = $this->requireUser();
        ApiResponse::success($this->users->serialize($user));
    }

    public function update(): void
    {
        $user = $this->requireUser();
        $displayName = trim((string) $this->request->input('display_name', ''));

        if ($displayName === '') {
            ApiResponse::error('validation_error', 'Имя отображения обязательно.', 422, ['display_name' => 'Заполните имя']);
        }

        if (mb_strlen($displayName) > 120) {
            ApiResponse::error('validation_error', 'Имя слишком длинное.', 422, ['display_name' => 'Максимум 120 символов']);
        }

        $updated = $this->users->updateDisplayName((int) $user['id'], $displayName);
        ApiResponse::success($this->users->serialize($updated));
    }

    public function uploadAvatar(): void
    {
        $user = $this->requireUser();
        $file = $this->request->file('avatar');

        try {
            $avatarService = new AvatarService();
            $data = $avatarService->upload($file, (int) $user['id']);
            $data['user_id'] = (int) $user['id'];
            $fileId = (new FileRepository())->create($data);
            $updatedUser = $this->users->updateAvatar((int) $user['id'], $fileId);
            ApiResponse::success($this->users->serialize($updatedUser));
        } catch (\Throwable $e) {
            ApiResponse::error('avatar_upload_failed', $e->getMessage(), 422);
        }
    }

    public function deleteAvatar(): void
    {
        $user = $this->requireUser();
        $updated = $this->users->updateAvatar((int) $user['id'], null);
        ApiResponse::success($this->users->serialize($updated));
    }
}
