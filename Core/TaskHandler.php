<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
class TaskHandler {
    
    // Функции, которые ChatGPT может вызывать
    public static function getAvailableFunctions(): array {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'add_task',
                    'description' => 'Добавить новую задачу в todo список',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Название задачи'
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Описание задачи (опционально)',
                                'default' => ''
                            ],
                            'due_date' => [
                                'type' => 'string',
                                'description' => 'Дата выполнения в формате Y-m-d (сегодня, завтра, конкретная дата)',
                                'default' => 'today'
                            ],
                            'due_time' => [
                                'type' => 'string',
                                'description' => 'Время выполнения в формате H:i (опционально)',
                                'default' => '12:00'
                            ],
                            'priority' => [
                                'type' => 'string',
                                'description' => 'Приоритет задачи',
                                'enum' => ['low', 'medium', 'high'],
                                'default' => 'medium'
                            ]
                        ],
                        'required' => ['title']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'delete_task',
                    'description' => 'Удалить задачу из todo списка',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'task_id' => [
                                'type' => 'integer',
                                'description' => 'ID задачи для удаления'
                            ]
                        ],
                        'required' => ['task_id']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_tasks',
                    'description' => 'Получить список задач',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'filter' => [
                                'type' => 'string',
                                'description' => 'Фильтр для задач',
                                'enum' => ['all', 'today', 'tomorrow', 'pending', 'completed'],
                                'default' => 'all'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    // Обработка вызова функции от ChatGPT
    public static function handleFunctionCall($functionName, $arguments, $userId): array {
        // Логируем вызов функции для отладки
        error_log('TaskHandler::handleFunctionCall - Function: ' . $functionName . ', Arguments: ' . json_encode($arguments) . ', UserId: ' . $userId);
        
        switch ($functionName) {
            case 'add_task':
                return self::addTask($arguments, $userId);
            case 'delete_task':
                return self::deleteTask($arguments, $userId);
            case 'list_tasks':
                return self::listTasks($arguments, $userId);
            default:
                error_log('TaskHandler::handleFunctionCall - Unknown function: ' . $functionName);
                return [
                    'success' => false,
                    'message' => 'Неизвестная функция: ' . $functionName
                ];
        }
    }
    
    // Добавление задачи
    public static function addTask($args, $userId): array {
        try {
            // Логируем входящие параметры для отладки
            error_log('TaskHandler::addTask called with args: ' . json_encode($args) . ', userId: ' . $userId);
            
            $title = $args['title'] ?? '';
            $description = $args['description'] ?? '';
            $dueDate = self::parseDate($args['due_date'] ?? 'today');
            $dueTime = $args['due_time'] ?? '12:00';
            $priority = $args['priority'] ?? 'medium';
            
            if (empty($title)) {
                return [
                    'success' => false,
                    'message' => 'Название задачи не может быть пустым'
                ];
            }
            
            // Валидация времени
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dueTime)) {
                $dueTime = '12:00';
            }
            
            // Валидация приоритета
            if (!in_array($priority, ['low', 'medium', 'high'])) {
                $priority = 'medium';
            }
            
            $createdDate = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO `Tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `due_time`, `priority`, `reminder`, `status`, `created_at`, `updated_at`, `reminder_sent`) VALUES
(0, ?, ?, ?, ?, ?, ?, '5min', 'pending', ?, ?, 0)";
            
            $mysqli = self::getConnection();
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('issssss', $userId, $title, $description, $dueDate, $dueTime, $priority, $createdDate, $createdDate);
            
            if ($stmt->execute()) {
                $taskId = $mysqli->insert_id;
                $stmt->close();
                $mysqli->close();
                
                return [
                    'success' => true,
                    'message' => "✅ Задача '$title' успешно добавлена на $dueDate в $dueTime",
                    'task_id' => $taskId,
                    'task' => [
                        'title' => $title,
                        'description' => $description,
                        'due_date' => $dueDate,
                        'due_time' => $dueTime,
                        'priority' => $priority
                    ]
                ];
            } else {
                $stmt->close();
                $mysqli->close();
                return [
                    'success' => false,
                    'message' => 'Ошибка при добавлении задачи в базу данных'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    // Удаление задачи
    public static function deleteTask($args, $userId): array {
        try {
            $taskId = $args['task_id'] ?? 0;
            
            if (!$taskId) {
                return [
                    'success' => false,
                    'message' => 'ID задачи не указан'
                ];
            }
            
            $sql = "DELETE FROM `Tasks` WHERE `id` = ? AND `user_id` = ?";
            $mysqli = self::getConnection();
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ii', $taskId, $userId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $mysqli->close();
                
                return [
                    'success' => true,
                    'message' => "✅ Задача с ID $taskId успешно удалена"
                ];
            } else {
                $stmt->close();
                $mysqli->close();
                
                return [
                    'success' => false,
                    'message' => 'Задача не найдена или у вас нет прав на её удаление'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    // Получение списка задач
    public static function listTasks($args, $userId): array {
        try {
            $filter = $args['filter'] ?? 'all';
            
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
            
            $mysqli = self::getConnection();
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
            
            if (empty($tasks)) {
                return [
                    'success' => true,
                    'message' => 'У вас пока нет задач',
                    'tasks' => []
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Вот ваши задачи:',
                'tasks' => $tasks,
                'count' => count($tasks)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    // Парсинг даты из естественного языка
    public static function parseDate($dateInput): string {
        $dateInput = strtolower(trim($dateInput));
        
        switch ($dateInput) {
            case 'today':
            case 'сегодня':
                return date('Y-m-d');
            case 'tomorrow':
            case 'завтра':
                return date('Y-m-d', strtotime('+1 day'));
            case 'yesterday':
            case 'вчера':
                return date('Y-m-d', strtotime('-1 day'));
            default:
                // Пытаемся распарсить конкретную дату
                $parsed = strtotime($dateInput);
                if ($parsed !== false) {
                    return date('Y-m-d', $parsed);
                }
                // Если не удалось распарсить, возвращаем сегодня
                return date('Y-m-d');
        }
    }
    
    // Получение соединения с базой данных
    public static function getConnection() {
        require_once __DIR__ . '/../config.php';
        
        $host = 'localhost';
        $user = DB_NAME;
        $pass = DB_PASSWORD;
        $db = DB_NAME;
        
        // Логируем параметры подключения для отладки
        error_log('TaskHandler::getConnection - Host: ' . $host . ', User: ' . $user . ', DB: ' . $db);
        
        $mysqli = new mysqli($host, $user, $pass, $db);
        
        if ($mysqli->connect_error) {
            error_log('TaskHandler::getConnection error: ' . $mysqli->connect_error);
            throw new Exception("Ошибка подключения к базе данных: " . $mysqli->connect_error);
        }
        
        error_log('TaskHandler::getConnection successful');
        return $mysqli;
    }
}

try {
    $mysqli = TaskHandler::getConnection();
    $mysqli->close();
    echo "✅ Соединение с базой данных успешно установлено";
} catch (Exception $e) {
    echo "❌ Ошибка соединения: " . $e->getMessage();
}
