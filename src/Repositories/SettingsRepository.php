<?php
namespace MiniApp\Repositories;

use MiniApp\Core\Database;
use PDO;

class SettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_settings WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $settings = $stmt->fetch();

        if ($settings) {
            return $this->serialize($settings);
        }

        $this->pdo->prepare('INSERT INTO user_settings (user_id, theme_mode, accent_color, week_start, created_at, updated_at) VALUES (:user_id, :theme_mode, :accent_color, :week_start, :created_at, :updated_at)')
            ->execute([
                'user_id' => $userId,
                'theme_mode' => 'system',
                'accent_color' => 'blue',
                'week_start' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->getByUserId($userId);
    }

    public function updateTheme(int $userId, array $data): array
    {
        $existing = $this->getByUserId($userId);
        $stmt = $this->pdo->prepare('UPDATE user_settings SET theme_mode = :theme_mode, accent_color = :accent_color, week_start = :week_start, updated_at = :updated_at WHERE user_id = :user_id');
        $stmt->execute([
            'theme_mode' => $data['theme_mode'] ?? $existing['theme_mode'],
            'accent_color' => $data['accent_color'] ?? $existing['accent_color'],
            'week_start' => $data['week_start'] ?? $existing['week_start'],
            'updated_at' => now(),
            'user_id' => $userId,
        ]);

        return $this->getByUserId($userId);
    }

    public function serialize(array $row): array
    {
        return [
            'theme_mode' => $row['theme_mode'],
            'accent_color' => $row['accent_color'],
            'week_start' => (int) $row['week_start'],
        ];
    }
}
