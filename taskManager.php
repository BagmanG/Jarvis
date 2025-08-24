<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Обработка CORS preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Получаем данные из POST запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

$action = $input['action'] ?? '';
$data = $input['data'] ?? [];

switch ($action) {
    case 'add_task':
        $result = addTask($data);
        break;
    case 'delete_task':
        $result = deleteTask($data);
        break;
    case 'list_tasks':
        $result = listTasks($data);
        break;
    default:
        $result = ['error' => 'Unknown action'];
}

echo json_encode($result);

function addTask($data) {
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $dueDate = $data['due_date'] ?? date('Y-m-d');
    $dueTime = $data['due_time'] ?? '12:00';
    $priority = $data['priority'] ?? 'medium';
    $userId = $data['user_id'] ?? 0;
    
    if (empty($title) || empty($userId)) {
        return ['error' => 'Title and user_id are required'];
    }
    
    $createdDate = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO `Tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `due_time`, `priority`, `reminder`, `status`, `created_at`, `updated_at`, `reminder_sent`) VALUES
(0, ?, ?, ?, ?, ?, ?, '5min', 'pending', ?, ?, 0)";
    
    $mysqli = getConnection();
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('issssss', $userId, $title, $description, $dueDate, $dueTime, $priority, $createdDate, $createdDate);
    
    if ($stmt->execute()) {
        $taskId = $mysqli->insert_id;
        $stmt->close();
        $mysqli->close();
        
        return [
            'success' => true,
            'message' => "Task '$title' added successfully",
            'task_id' => $taskId
        ];
    } else {
        $stmt->close();
        $mysqli->close();
        return ['error' => 'Database error'];
    }
}

function deleteTask($data) {
    $taskId = $data['task_id'] ?? 0;
    $userId = $data['user_id'] ?? 0;
    
    if (!$taskId || !$userId) {
        return ['error' => 'Task ID and user_id are required'];
    }
    
    $sql = "DELETE FROM `Tasks` WHERE `id` = ? AND `user_id` = ?";
    $mysqli = getConnection();
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $taskId, $userId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        $mysqli->close();
        
        return [
            'success' => true,
            'message' => "Task deleted successfully"
        ];
    } else {
        $stmt->close();
        $mysqli->close();
        
        return ['error' => 'Task not found or access denied'];
    }
}

function listTasks($data) {
    $userId = $data['user_id'] ?? 0;
    $filter = $data['filter'] ?? 'all';
    
    if (!$userId) {
        return ['error' => 'User ID is required'];
    }
    
    $sql = "SELECT * FROM `Tasks` WHERE `user_id` = ?";
    $params = [$userId];
    $types = 'i';
    
    switch ($filter) {
        case 'today':
            $sql .= " AND `due_date` = CURDATE()";
            break;
        case 'tomorrow':
            $sql .= " AND `due_date` = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'pending':
            $sql .= " AND `status` = 'pending'";
            break;
        case 'completed':
            $sql .= " AND `status` = 'completed'";
            break;
    }
    
    $sql .= " ORDER BY `due_date` ASC, `due_time` ASC";
    
    $mysqli = getConnection();
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    $stmt->close();
    $mysqli->close();
    
    return [
        'success' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ];
}


function getConnection() {
    $host = 'localhost';
    $user = DB_NAME;
    $pass = DB_PASSWORD;
    $db = DB_NAME;
    
    // Подключаемся к базе
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    // Проверяем подключение
    if ($mysqli->connect_error) {
        throw new Exception("Ошибка подключения: " . $mysqli->connect_error);
    }
    
    return $mysqli;
}
?>