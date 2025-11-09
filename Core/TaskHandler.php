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
                    'description' => 'Удалить задачу из todo списка. Можно указать либо task_id, либо title (название задачи). Если указано название, будет удалена первая найденная задача с таким названием. Можно также указать due_date для более точного поиска (сегодня, завтра, или конкретная дата в формате Y-m-d). ВАЖНО: Если пользователь указывает дату вместе с названием задачи (например "удали задачу на завтра купить колу"), обязательно используй параметр due_date для точного поиска задачи.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'task_id' => [
                                'type' => 'integer',
                                'description' => 'ID задачи для удаления (опционально, если указан title)'
                            ],
                            'title' => [
                                'type' => 'string',
                                'description' => 'Название задачи для удаления (опционально, если указан task_id)'
                            ],
                            'due_date' => [
                                'type' => 'string',
                                'description' => 'Дата задачи для более точного поиска (сегодня, завтра, или конкретная дата в формате Y-m-d). Используется вместе с title для поиска задачи по названию и дате.'
                            ]
                        ]
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
        // Отправляем отладочную информацию в Telegram
        if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
            ///DEBUG
            //sendMessage($GLOBALS['debug_chat_id'], "🔧 TaskHandler::handleFunctionCall - Функция: $functionName, Аргументы: " . json_encode($arguments) . ", UserId: $userId");
        }
        
        switch ($functionName) {
            case 'add_task':
                return self::addTask($arguments, $userId);
            case 'delete_task':
                return self::deleteTask($arguments, $userId);
            case 'list_tasks':
                return self::listTasks($arguments, $userId);
            default:
                if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
                    ///DEBUG
                    //sendMessage($GLOBALS['debug_chat_id'], "❌ TaskHandler::handleFunctionCall - Неизвестная функция: $functionName");
                }
                return [
                    'success' => false,
                    'message' => 'Неизвестная функция: ' . $functionName
                ];
        }
    }
    
    // Добавление задачи
    public static function addTask($args, $userId): array {
        try {
            // Отправляем отладочную информацию в Telegram
            if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
                //sendMessage($GLOBALS['debug_chat_id'], "🔧 TaskHandler::addTask вызван с аргументами: " . json_encode($args) . ", userId: $userId");
            }
            
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
    (0, $userId, '$title', '$description', '$dueDate', '$dueTime', '$priority', '5min', 'pending', '$createdDate', '$createdDate', 0)";
            
            $mysqli = self::getConnection();
            
            if ($mysqli->query($sql)) {
                $taskId = $mysqli->insert_id;
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
                $mysqli->close();
                return [
                    'success' => false,
                    'message' => 'Ошибка при добавлении задачи в базу данных: ' . $mysqli->error
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
            $title = $args['title'] ?? '';
            $dueDate = $args['due_date'] ?? '';
            
            $mysqli = self::getConnection();
            
            // Если передан title, сначала находим задачу по названию (и дате, если указана)
            if (!empty($title) && !$taskId) {
                // Парсим дату, если она указана
                $parsedDate = '';
                if (!empty($dueDate)) {
                    $parsedDate = self::parseDate($dueDate);
                }
                
                // Строим SQL запрос с учетом даты
                if (!empty($parsedDate)) {
                    // Сначала пробуем точное совпадение по названию и дате (без учета регистра)
                    $findSql = "SELECT `id`, `title`, `due_date` FROM `Tasks` WHERE `user_id` = ? AND LOWER(`title`) = LOWER(?) AND `due_date` = ? LIMIT 1";
                    $findStmt = $mysqli->prepare($findSql);
                    $findStmt->bind_param('iss', $userId, $title, $parsedDate);
                    $findStmt->execute();
                    $result = $findStmt->get_result();
                    
                    // Если точное совпадение не найдено, пробуем частичное совпадение по названию с датой
                    if ($result->num_rows == 0) {
                        $findStmt->close();
                        $findSql = "SELECT `id`, `title`, `due_date` FROM `Tasks` WHERE `user_id` = ? AND LOWER(`title`) LIKE LOWER(?) AND `due_date` = ? LIMIT 1";
                        $findStmt = $mysqli->prepare($findSql);
                        $searchTitle = '%' . $title . '%';
                        $findStmt->bind_param('iss', $userId, $searchTitle, $parsedDate);
                        $findStmt->execute();
                        $result = $findStmt->get_result();
                    }
                } else {
                    // Если дата не указана, ищем только по названию
                    // Сначала пробуем точное совпадение (без учета регистра)
                    $findSql = "SELECT `id`, `title` FROM `Tasks` WHERE `user_id` = ? AND LOWER(`title`) = LOWER(?) LIMIT 1";
                    $findStmt = $mysqli->prepare($findSql);
                    $findStmt->bind_param('is', $userId, $title);
                    $findStmt->execute();
                    $result = $findStmt->get_result();
                    
                    // Если точное совпадение не найдено, пробуем частичное совпадение
                    if ($result->num_rows == 0) {
                        $findStmt->close();
                        $findSql = "SELECT `id`, `title` FROM `Tasks` WHERE `user_id` = ? AND LOWER(`title`) LIKE LOWER(?) LIMIT 1";
                        $findStmt = $mysqli->prepare($findSql);
                        $searchTitle = '%' . $title . '%';
                        $findStmt->bind_param('is', $userId, $searchTitle);
                        $findStmt->execute();
                        $result = $findStmt->get_result();
                    }
                }
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $taskId = $row['id'];
                    $findStmt->close();
                } else {
                    $findStmt->close();
                    $mysqli->close();
                    $dateMsg = !empty($parsedDate) ? " на дату $parsedDate" : "";
                    return [
                        'success' => false,
                        'message' => "❌ Задача с названием '$title'$dateMsg не найдена"
                    ];
                }
            }
            
            // Проверяем, что у нас есть task_id для удаления
            if (!$taskId) {
                $mysqli->close();
                return [
                    'success' => false,
                    'message' => 'Не указан ID задачи или название задачи для удаления'
                ];
            }
            
            // Получаем название задачи перед удалением для сообщения
            $getTitleSql = "SELECT `title` FROM `Tasks` WHERE `id` = ? AND `user_id` = ?";
            $getTitleStmt = $mysqli->prepare($getTitleSql);
            $getTitleStmt->bind_param('ii', $taskId, $userId);
            $getTitleStmt->execute();
            $titleResult = $getTitleStmt->get_result();
            $taskTitle = '';
            if ($titleResult->num_rows > 0) {
                $titleRow = $titleResult->fetch_assoc();
                $taskTitle = $titleRow['title'];
            }
            $getTitleStmt->close();
            
            // Удаляем задачу
            $sql = "DELETE FROM `Tasks` WHERE `id` = ? AND `user_id` = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ii', $taskId, $userId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $mysqli->close();
                
                $message = !empty($taskTitle) 
                    ? "✅ Задача '$taskTitle' успешно удалена"
                    : "✅ Задача с ID $taskId успешно удалена";
                
                return [
                    'success' => true,
                    'message' => $message
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
        require_once __DIR__ . '/DateTimeHelper.php';
        
        $dateInput = strtolower(trim($dateInput));
        
        switch ($dateInput) {
            case 'today':
            case 'сегодня':
                return DateTimeHelper::getCurrentDateTime()['date'];
            case 'tomorrow':
            case 'завтра':
                return DateTimeHelper::getTomorrowDate();
            case 'yesterday':
            case 'вчера':
                return DateTimeHelper::getYesterdayDate();
            default:
                // Пытаемся распарсить конкретную дату
                $parsed = strtotime($dateInput);
                if ($parsed !== false) {
                    return date('Y-m-d', $parsed);
                }
                // Если не удалось распарсить, возвращаем сегодня
                return DateTimeHelper::getCurrentDateTime()['date'];
        }
    }
    
    // Получение соединения с базой данных
    public static function getConnection() {
        require_once __DIR__ . '/../config.php';
        
        $host = 'localhost';
        $user = DB_NAME;
        $pass = DB_PASSWORD;
        $db = DB_NAME;
        
        // Отправляем параметры подключения в Telegram
        if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
            //sendMessage($GLOBALS['debug_chat_id'], "🔌 TaskHandler::getConnection - Host: $host, User: $user, DB: $db");
        }
        
        $mysqli = new mysqli($host, $user, $pass, $db);
        
        if ($mysqli->connect_error) {
            if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
                //sendMessage($GLOBALS['debug_chat_id'], "❌ TaskHandler::getConnection ошибка: " . $mysqli->connect_error);
            }
            throw new Exception("Ошибка подключения к базе данных: " . $mysqli->connect_error);
        }
        
        if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
            //sendMessage($GLOBALS['debug_chat_id'], "✅ TaskHandler::getConnection успешно");
        }
        return $mysqli;
    }
}
