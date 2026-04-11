<?php
namespace MiniApp\Repositories;

use MiniApp\Core\Database;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT u.*, f.path as avatar_path, f.thumbnail_path as avatar_thumbnail_path FROM users u LEFT JOIN files f ON f.id = u.avatar_file_id WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE telegram_id = :telegram_id LIMIT 1');
        $stmt->execute(['telegram_id' => $telegramId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function createOrUpdateFromTelegram(array $telegramUser): array
    {
        $telegramId = (int) ($telegramUser['id'] ?? 0);
        $existing = $this->findByTelegramId($telegramId);

        $payload = [
            'telegram_id' => $telegramId,
            'telegram_username' => $telegramUser['username'] ?? null,
            'first_name' => $telegramUser['first_name'] ?? null,
            'last_name' => $telegramUser['last_name'] ?? null,
            'display_name' => trim(($telegramUser['first_name'] ?? '') . ' ' . ($telegramUser['last_name'] ?? '')) ?: ($telegramUser['username'] ?? 'Пользователь'),
            'updated_at' => now(),
        ];

        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE users SET telegram_username = :telegram_username, first_name = :first_name, last_name = :last_name, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                'telegram_username' => $payload['telegram_username'],
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'updated_at' => $payload['updated_at'],
                'id' => $existing['id'],
            ]);
            return $this->findById((int) $existing['id']);
        }

        $stmt = $this->pdo->prepare('INSERT INTO users (telegram_id, telegram_username, first_name, last_name, display_name, created_at, updated_at) VALUES (:telegram_id, :telegram_username, :first_name, :last_name, :display_name, :created_at, :updated_at)');
        $stmt->execute([
            'telegram_id' => $payload['telegram_id'],
            'telegram_username' => $payload['telegram_username'],
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'display_name' => $payload['display_name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = (int) $this->pdo->lastInsertId();
        return $this->findById($userId);
    }

    public function updateDisplayName(int $userId, string $displayName): array
    {
        $stmt = $this->pdo->prepare('UPDATE users SET display_name = :display_name, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'display_name' => $displayName,
            'updated_at' => now(),
            'id' => $userId,
        ]);

        return $this->findById($userId);
    }

    public function updateAvatar(int $userId, ?int $fileId): array
    {
        $stmt = $this->pdo->prepare('UPDATE users SET avatar_file_id = :avatar_file_id, updated_at = :updated_at WHERE id = :id');
        $stmt->bindValue(':avatar_file_id', $fileId, $fileId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':updated_at', now());
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->findById($userId);
    }

    public function serialize(array $user): array
    {
        $displayName = $user['display_name'] ?: trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['telegram_username'] ?? 'Пользователь');

        return [
            'id' => (int) $user['id'],
            'telegram_id' => isset($user['telegram_id']) ? (int) $user['telegram_id'] : null,
            'telegram_username' => $user['telegram_username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'display_name' => $displayName,
            'avatar_url' => !empty($user['avatar_path']) ? public_url($user['avatar_path']) : null,
            'avatar_thumbnail_url' => !empty($user['avatar_thumbnail_path']) ? public_url($user['avatar_thumbnail_path']) : null,
        ];
    }
}
