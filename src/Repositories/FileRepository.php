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
