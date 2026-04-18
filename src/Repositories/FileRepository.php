<?php
namespace MiniApp\Repositories;

use MiniApp\Core\Database;
use PDO;

class FileRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }



    public function findByUserAndType(int $userId, string $type): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM files WHERE user_id = :user_id AND type = :type ORDER BY id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId, 'type' => $type]);
        $file = $stmt->fetch();
        return $file ?: null;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE files SET original_name = :original_name, stored_name = :stored_name, mime_type = :mime_type, extension = :extension, size_bytes = :size_bytes, path = :path, thumbnail_path = :thumbnail_path WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'original_name' => $data['original_name'],
            'stored_name' => $data['stored_name'],
            'mime_type' => $data['mime_type'],
            'extension' => $data['extension'],
            'size_bytes' => $data['size_bytes'],
            'path' => $data['path'],
            'thumbnail_path' => $data['thumbnail_path'],
        ]);
    }

    public function upsertByUserAndType(array $data): int
    {
        $existing = $this->findByUserAndType((int) $data['user_id'], (string) $data['type']);
        if ($existing) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        return $this->create($data);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO files (user_id, type, original_name, stored_name, mime_type, extension, size_bytes, path, thumbnail_path, created_at) VALUES (:user_id, :type, :original_name, :stored_name, :mime_type, :extension, :size_bytes, :path, :thumbnail_path, :created_at)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'original_name' => $data['original_name'],
            'stored_name' => $data['stored_name'],
            'mime_type' => $data['mime_type'],
            'extension' => $data['extension'],
            'size_bytes' => $data['size_bytes'],
            'path' => $data['path'],
            'thumbnail_path' => $data['thumbnail_path'],
            'created_at' => now(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM files WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $file = $stmt->fetch();
        return $file ?: null;
    }
}
