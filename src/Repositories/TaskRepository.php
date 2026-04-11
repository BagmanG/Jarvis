<?php
namespace MiniApp\Repositories;

use MiniApp\Core\Database;
use PDO;

class TaskRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getMonthSummary(int $userId, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));

        $stmt = $this->pdo->prepare('SELECT task_date, COUNT(*) AS total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed, MAX(CASE priority WHEN "high" THEN 3 WHEN "medium" THEN 2 ELSE 1 END) AS priority_level FROM tasks WHERE user_id = :user_id AND task_date BETWEEN :date_from AND :date_to AND deleted_at IS NULL GROUP BY task_date');
        $stmt->execute([
            'user_id' => $userId,
            'date_from' => $from,
            'date_to' => $to,
        ]);

        $summary = [];
        foreach ($stmt->fetchAll() as $row) {
            $summary[$row['task_date']] = [
                'total' => (int) $row['total'],
                'completed' => (int) $row['completed'],
                'priority_level' => (int) $row['priority_level'],
            ];
        }

        return $summary;
    }

    public function getByDate(int $userId, string $date): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE user_id = :user_id AND task_date = :task_date AND deleted_at IS NULL ORDER BY all_day DESC, time_start ASC, position ASC, id ASC');
        $stmt->execute([
            'user_id' => $userId,
            'task_date' => $date,
        ]);

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    public function getRange(int $userId, string $from, string $to): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE user_id = :user_id AND task_date BETWEEN :date_from AND :date_to AND deleted_at IS NULL ORDER BY task_date ASC, all_day DESC, time_start ASC, position ASC, id ASC');
        $stmt->execute([
            'user_id' => $userId,
            'date_from' => $from,
            'date_to' => $to,
        ]);

        return array_map([$this, 'serialize'], $stmt->fetchAll());
    }

    public function findById(int $userId, int $taskId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([
            'id' => $taskId,
            'user_id' => $userId,
        ]);
        $task = $stmt->fetch();
        return $task ? $this->serialize($task) : null;
    }

    public function create(int $userId, array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO tasks (user_id, title, description, task_date, time_start, time_end, all_day, priority, color, status, position, created_at, updated_at) VALUES (:user_id, :title, :description, :task_date, :time_start, :time_end, :all_day, :priority, :color, :status, :position, :created_at, :updated_at)');
        $stmt->execute([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'],
            'task_date' => $data['task_date'],
            'time_start' => $data['time_start'],
            'time_end' => $data['time_end'],
            'all_day' => $data['all_day'],
            'priority' => $data['priority'],
            'color' => $data['color'],
            'status' => $data['status'],
            'position' => $data['position'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->findById($userId, (int) $this->pdo->lastInsertId());
    }

    public function update(int $userId, int $taskId, array $data): ?array
    {
        $existing = $this->findRawById($userId, $taskId);
        if (!$existing) {
            return null;
        }

        $merged = array_merge($existing, $data);
        $stmt = $this->pdo->prepare('UPDATE tasks SET title = :title, description = :description, task_date = :task_date, time_start = :time_start, time_end = :time_end, all_day = :all_day, priority = :priority, color = :color, status = :status, position = :position, updated_at = :updated_at WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'title' => $merged['title'],
            'description' => $merged['description'],
            'task_date' => $merged['task_date'],
            'time_start' => $merged['time_start'],
            'time_end' => $merged['time_end'],
            'all_day' => $merged['all_day'],
            'priority' => $merged['priority'],
            'color' => $merged['color'],
            'status' => $merged['status'],
            'position' => $merged['position'] ?? 0,
            'updated_at' => now(),
            'id' => $taskId,
            'user_id' => $userId,
        ]);

        return $this->findById($userId, $taskId);
    }

    public function updateStatus(int $userId, int $taskId, string $status): ?array
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET status = :status, updated_at = :updated_at WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        $stmt->execute([
            'status' => $status,
            'updated_at' => now(),
            'id' => $taskId,
            'user_id' => $userId,
        ]);

        return $this->findById($userId, $taskId);
    }

    public function softDelete(int $userId, int $taskId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET deleted_at = :deleted_at, updated_at = :updated_at WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        return $stmt->execute([
            'deleted_at' => now(),
            'updated_at' => now(),
            'id' => $taskId,
            'user_id' => $userId,
        ]);
    }

    private function findRawById(int $userId, int $taskId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $taskId, 'user_id' => $userId]);
        $task = $stmt->fetch();
        return $task ?: null;
    }

    public function serialize(array $task): array
    {
        return [
            'id' => (int) $task['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'date' => $task['task_date'],
            'time_start' => $task['time_start'] ? substr($task['time_start'], 0, 5) : null,
            'time_end' => $task['time_end'] ? substr($task['time_end'], 0, 5) : null,
            'all_day' => (bool) $task['all_day'],
            'priority' => $task['priority'],
            'color' => $task['color'] ?: 'blue',
            'status' => $task['status'],
            'position' => isset($task['position']) ? (int) $task['position'] : 0,
            'created_at' => $task['created_at'],
            'updated_at' => $task['updated_at'],
        ];
    }
}
