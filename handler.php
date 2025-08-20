<?php
header('Content-Type: application/json');
require_once 'config.php';

class TaskHandler {
    private $conn;
    private $timezone = 'Europe/Moscow'; // UTC+3

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed']));
        }
        date_default_timezone_set($this->timezone);
    }

    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? 0;

        switch ($action) {
            case 'add':
                return $this->addTask($userId);
            case 'get':
                return $this->getTasks($userId);
            case 'update':
                return $this->updateTask($userId);
            case 'delete':
                return $this->deleteTask($userId);
            case 'stats':
                return $this->getStats($userId);
            case 'search':
                return $this->searchTasks($userId);
            case 'cron':
                return $this->handleCron();
            case 'save_user':
                return $this->saveUser();
            default:
                return json_encode(['error' => 'Invalid action']);
        }
    }

    private function addTask($userId) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $this->conn->prepare("
            INSERT INTO Tasks (user_id, title, description, due_date, due_time, priority, reminder)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issssss", 
            $userId,
            $data['title'],
            $data['description'],
            $data['due_date'],
            $data['due_time'],
            $data['priority'],
            $data['reminder']
        );

        if ($stmt->execute()) {
            return json_encode(['success' => true, 'task_id' => $stmt->insert_id]);
        } else {
            return json_encode(['error' => 'Failed to add task']);
        }
    }

    private function getTasks($userId) {
        $filter = $_GET['filter'] ?? 'all';
        $status = $_GET['status'] ?? 'pending';
        
        $sql = "SELECT * FROM Tasks WHERE user_id = ?";
        $params = [$userId];
        $types = "i";
        
        if ($filter === 'today') {
            $sql .= " AND due_date = CURDATE()";
        } elseif ($filter === 'tomorrow') {
            $sql .= " AND due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        } elseif ($filter === 'completed') {
            $sql .= " AND status = 'completed'";
        } else {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY due_date, due_time";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($filter === 'all') {
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param("i", $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        
        return json_encode(['tasks' => $tasks]);
    }

    private function updateTask($userId) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $this->conn->prepare("
            UPDATE Tasks 
            SET title = ?, description = ?, due_date = ?, due_time = ?, 
                priority = ?, reminder = ?, status = ?, reminder_sent = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $reminderSent = $data['reminder_sent'] ?? false;
        $stmt->bind_param("ssssssssii",
            $data['title'],
            $data['description'],
            $data['due_date'],
            $data['due_time'],
            $data['priority'],
            $data['reminder'],
            $data['status'],
            $reminderSent,
            $data['task_id'],
            $userId
        );

        if ($stmt->execute()) {
            return json_encode(['success' => true]);
        } else {
            return json_encode(['error' => 'Failed to update task']);
        }
    }

    private function deleteTask($userId) {
        $taskId = $_POST['task_id'] ?? 0;
        
        $stmt = $this->conn->prepare("DELETE FROM Tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $taskId, $userId);
        
        if ($stmt->execute()) {
            return json_encode(['success' => true]);
        } else {
            return json_encode(['error' => 'Failed to delete task']);
        }
    }

    private function getStats($userId) {
        $stmt = $this->conn->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                COUNT(CASE WHEN due_date = CURDATE() THEN 1 END) as today,
                COUNT(CASE WHEN due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as tomorrow
            FROM Tasks 
            WHERE user_id = ?
            GROUP BY status
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[$row['status']] = $row;
        }
        
        return json_encode(['stats' => $stats]);
    }

    private function searchTasks($userId) {
        $query = $_GET['q'] ?? '';
        $stmt = $this->conn->prepare("
            SELECT * FROM Tasks 
            WHERE user_id = ? AND (title LIKE ? OR description LIKE ?)
            ORDER BY due_date, due_time
        ");
        
        $searchTerm = "%$query%";
        $stmt->bind_param("iss", $userId, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        
        return json_encode(['tasks' => $tasks]);
    }

    public function handleCron() {
        // Этот метод будет вызываться cron job для отправки напоминаний
        $botToken = BOT_TOKEN;
        
        // Находим задачи, которые требуют напоминания
        $now = date('Y-m-d H:i:s');
        
        // Получаем задачи для напоминания
        $tasksToRemind = $this->getTasksForReminder();
        
        foreach ($tasksToRemind as $task) {
            $this->sendReminder($task, $botToken);
            
            // Помечаем напоминание как отправленное
            $updateStmt = $this->conn->prepare("
                UPDATE Tasks SET reminder_sent = TRUE WHERE id = ?
            ");
            $updateStmt->bind_param("i", $task['id']);
            $updateStmt->execute();
        }
        
        return json_encode(['processed' => count($tasksToRemind)]);
    }

    private function getTasksForReminder() {
        $tasks = [];
        $reminderTypes = ['30min', '5min', '1min'];
        
        foreach ($reminderTypes as $reminderType) {
            $reminderTime = $this->calculateReminderTime($reminderType);
            
            $stmt = $this->conn->prepare("
                SELECT t.*, u.chat_id 
                FROM Tasks t
                JOIN users u ON t.user_id = u.user_id
                WHERE t.reminder = ? 
                AND t.due_date = DATE(?) 
                AND t.due_time = TIME(?)
                AND t.reminder_sent = FALSE
                AND t.status = 'pending'
            ");
            
            $stmt->bind_param("sss", $reminderType, $reminderTime, $reminderTime);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($task = $result->fetch_assoc()) {
                $tasks[] = $task;
            }
        }
        
        return $tasks;
    }

    private function calculateReminderTime($reminderType) {
        $now = new DateTime();
        
        switch ($reminderType) {
            case '30min':
                $now->modify('+30 minutes');
                break;
            case '5min':
                $now->modify('+5 minutes');
                break;
            case '1min':
                $now->modify('+1 minute');
                break;
            default:
                return date('Y-m-d H:i:s');
        }
        
        return $now->format('Y-m-d H:i:s');
    }

    private function sendReminder($task, $botToken) {
        $message = "🔔 Напоминание!\n";
        $message .= "Задача: {$task['title']}\n";
        $message .= "Время: {$task['due_date']} {$task['due_time']}\n";
        $message .= "Приоритет: " . $this->getPriorityText($task['priority']);
        
        if (!empty($task['description'])) {
            $message .= "\nОписание: {$task['description']}";
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $task['chat_id'],
            'text' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    private function getPriorityText($priority) {
        $priorities = [
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий'
        ];
        return $priorities[$priority] ?? 'Средний';
    }

    private function saveUser() {
        $userId = $_POST['user_id'] ?? 0;
        $chatId = $_POST['chat_id'] ?? $userId;
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $username = $_POST['username'] ?? '';
        
        $stmt = $this->conn->prepare("
            INSERT INTO users (user_id, chat_id, first_name, last_name, username) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            chat_id = VALUES(chat_id),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            username = VALUES(username)
        ");
        
        $stmt->bind_param("iisss", $userId, $chatId, $firstName, $lastName, $username);
        
        if ($stmt->execute()) {
            return json_encode(['success' => true]);
        } else {
            return json_encode(['error' => 'Failed to save user']);
        }
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Создаем экземпляр и обрабатываем запрос
$handler = new TaskHandler();
echo $handler->handleRequest();
?>