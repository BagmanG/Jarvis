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
        $stmt = $this->pdo->prepare('INSERT INTO tasks (user_id, title, description, task_date, time_start, time_end, all_day, priority, color, status, reminder_minutes, reminder_sent_at, position, created_at, updated_at) VALUES (:user_id, :title, :description, :task_date, :time_start, :time_end, :all_day, :priority, :color, :status, :reminder_minutes, NULL, :position, :created_at, :updated_at)');
        $stmt->execute([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'task_date' => $data['task_date'],
            'time_start' => $data['time_start'] ?? null,
            'time_end' => $data['time_end'] ?? null,
            'all_day' => $data['all_day'] ?? 0,
            'priority' => $data['priority'] ?? 'medium',
            'color' => $data['color'] ?? 'blue',
            'status' => $data['status'] ?? 'active',
            'reminder_minutes' => $data['reminder_minutes'] ?? 5,
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
        $reminderChanged =
            (string) $existing['task_date'] !== (string) $merged['task_date'] ||
            (string) ($existing['time_start'] ?? '') !== (string) ($merged['time_start'] ?? '') ||
            (int) $existing['all_day'] !== (int) $merged['all_day'] ||
            (int) ($existing['reminder_minutes'] ?? 5) !== (int) ($merged['reminder_minutes'] ?? 5);

        $stmt = $this->pdo->prepare('UPDATE tasks SET title = :title, description = :description, task_date = :task_date, time_start = :time_start, time_end = :time_end, all_day = :all_day, priority = :priority, color = :color, status = :status, reminder_minutes = :reminder_minutes, reminder_sent_at = :reminder_sent_at, position = :position, updated_at = :updated_at WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        $stmt->execute([
            'title' => $merged['title'],
            'description' => $merged['description'] ?? null,
            'task_date' => $merged['task_date'],
            'time_start' => $merged['time_start'] ?? null,
            'time_end' => $merged['time_end'] ?? null,
            'all_day' => $merged['all_day'] ?? 0,
            'priority' => $merged['priority'] ?? 'medium',
            'color' => $merged['color'] ?? 'blue',
            'status' => $merged['status'] ?? 'active',
            'reminder_minutes' => $merged['reminder_minutes'] ?? 5,
            'reminder_sent_at' => $reminderChanged ? null : ($existing['reminder_sent_at'] ?? null),
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

    public function getDueReminders(): array
    {
        $sql = "SELECT t.*, u.telegram_id
            FROM tasks t
            INNER JOIN users u ON u.id = t.user_id
            WHERE t.deleted_at IS NULL
              AND t.status = 'active'
              AND t.reminder_sent_at IS NULL
              AND CASE
                    WHEN t.all_day = 1 THEN DATE_SUB(CONCAT(t.task_date, ' 09:00:00'), INTERVAL t.reminder_minutes MINUTE)
                    WHEN t.time_start IS NOT NULL THEN DATE_SUB(CONCAT(t.task_date, ' ', t.time_start), INTERVAL t.reminder_minutes MINUTE)
                    ELSE DATE_SUB(CONCAT(t.task_date, ' 09:00:00'), INTERVAL t.reminder_minutes MINUTE)
                  END <= NOW()
              AND CONCAT(t.task_date, ' ', COALESCE(t.time_start, '23:59:59')) >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY t.task_date ASC, t.time_start ASC, t.id ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function markReminderSent(int $taskId): void
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET reminder_sent_at = :reminder_sent_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => $taskId,
            'reminder_sent_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markReminderFailed(int $taskId): void
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'id' => $taskId,
            'updated_at' => now(),
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
            'reminder_minutes' => isset($task['reminder_minutes']) ? (int) $task['reminder_minutes'] : 5,
            'position' => isset($task['position']) ? (int) $task['position'] : 0,
            'created_at' => $task['created_at'],
            'updated_at' => $task['updated_at'],
        ];
    }
}
