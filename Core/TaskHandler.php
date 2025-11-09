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
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'complete_task',
                    'description' => 'Выполнить задачу (отметить как выполненную). Можно указать либо task_id, либо title (название задачи). Если указано название, будет выполнена первая найденная задача с таким названием. Можно также указать due_date для более точного поиска (сегодня, завтра, или конкретная дата в формате Y-m-d). ВАЖНО: Если пользователь указывает дату вместе с названием задачи (например "выполни задачу на завтра купить колу"), обязательно используй параметр due_date для точного поиска задачи.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'task_id' => [
                                'type' => 'integer',
                                'description' => 'ID задачи для выполнения (опционально, если указан title)'
                            ],
                            'title' => [
                                'type' => 'string',
                                'description' => 'Название задачи для выполнения (опционально, если указан task_id)'
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
                    'name' => 'get_efficiency_report',
                    'description' => 'Получить детальный отчёт по эффективности выполнения задач за месяц. Отчёт включает статистику по выполненным и невыполненным задачам, анализ по приоритетам, примеры задач и советы по улучшению эффективности.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'month_offset' => [
                                'type' => 'integer',
                                'description' => 'Смещение месяца относительно текущего (0 - текущий месяц, -1 - предыдущий месяц, 1 - следующий месяц)',
                                'default' => 0
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
            case 'complete_task':
                return self::completeTask($arguments, $userId);
            case 'get_efficiency_report':
                return self::getEfficiencyReport($arguments, $userId);
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
    
    // Выполнение задачи (отметка как выполненной)
    public static function completeTask($args, $userId): array {
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
            
            // Проверяем, что у нас есть task_id для выполнения
            if (!$taskId) {
                $mysqli->close();
                return [
                    'success' => false,
                    'message' => 'Не указан ID задачи или название задачи для выполнения'
                ];
            }
            
            // Получаем название задачи перед обновлением для сообщения
            $getTitleSql = "SELECT `title`, `status` FROM `Tasks` WHERE `id` = ? AND `user_id` = ?";
            $getTitleStmt = $mysqli->prepare($getTitleSql);
            $getTitleStmt->bind_param('ii', $taskId, $userId);
            $getTitleStmt->execute();
            $titleResult = $getTitleStmt->get_result();
            $taskTitle = '';
            $currentStatus = '';
            if ($titleResult->num_rows > 0) {
                $titleRow = $titleResult->fetch_assoc();
                $taskTitle = $titleRow['title'];
                $currentStatus = $titleRow['status'];
            }
            $getTitleStmt->close();
            
            // Проверяем, что задача существует
            if (empty($taskTitle)) {
                $mysqli->close();
                return [
                    'success' => false,
                    'message' => 'Задача не найдена или у вас нет прав на её выполнение'
                ];
            }
            
            // Если задача уже выполнена, сообщаем об этом
            if ($currentStatus === 'completed') {
                $mysqli->close();
                return [
                    'success' => true,
                    'message' => "✅ Задача '$taskTitle' уже была выполнена ранее"
                ];
            }
            
            // Обновляем статус задачи на 'completed'
            $updatedDate = date('Y-m-d H:i:s');
            $sql = "UPDATE `Tasks` SET `status` = 'completed', `updated_at` = ? WHERE `id` = ? AND `user_id` = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sii', $updatedDate, $taskId, $userId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                $mysqli->close();
                
                $message = !empty($taskTitle) 
                    ? "✅ Задача '$taskTitle' успешно выполнена"
                    : "✅ Задача с ID $taskId успешно выполнена";
                
                return [
                    'success' => true,
                    'message' => $message
                ];
            } else {
                $stmt->close();
                $mysqli->close();
                
                return [
                    'success' => false,
                    'message' => 'Не удалось выполнить задачу. Попробуйте позже.'
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
    
    // Получение отчёта по эффективности для GPT
    public static function getEfficiencyReport($args, $userId): array {
        try {
            $monthOffset = $args['month_offset'] ?? 0;
            return self::analyzeEfficiency($userId, $monthOffset);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при получении отчёта: ' . $e->getMessage()
            ];
        }
    }
    
    // Получение задач за месяц для анализа эффективности
    public static function getTasksForMonth($userId, $monthOffset = 0): array {
        try {
            $mysqli = self::getConnection();
            
            // Вычисляем начало и конец месяца
            $startDate = date('Y-m-01', strtotime("$monthOffset months"));
            $endDate = date('Y-m-t', strtotime("$monthOffset months"));
            
            $sql = "SELECT * FROM `Tasks` 
                    WHERE `user_id` = ? 
                    AND `due_date` >= ? 
                    AND `due_date` <= ?
                    ORDER BY `due_date` ASC, `due_time` ASC";
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('iss', $userId, $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
            
            $stmt->close();
            $mysqli->close();
            
            return $tasks;
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Анализ эффективности задач за месяц
    public static function analyzeEfficiency($userId, $monthOffset = 0): array {
        try {
            $tasks = self::getTasksForMonth($userId, $monthOffset);
            
            if (empty($tasks)) {
                return [
                    'success' => true,
                    'message' => 'У вас нет задач за этот период',
                    'report' => 'За выбранный период у вас не было задач. Начните добавлять задачи, чтобы отслеживать свою эффективность!'
                ];
            }
            
            $total = count($tasks);
            $completed = 0;
            $pending = 0;
            $overdue = 0;
            $onTime = 0;
            
            $priorityStats = ['low' => 0, 'medium' => 0, 'high' => 0];
            $priorityCompleted = ['low' => 0, 'medium' => 0, 'high' => 0];
            
            $today = date('Y-m-d');
            
            foreach ($tasks as $task) {
                // Подсчёт по статусам
                if ($task['status'] === 'completed') {
                    $completed++;
                    $priorityCompleted[$task['priority']]++;
                    
                    // Проверяем, выполнена ли задача вовремя
                    // Сравниваем дату выполнения (due_date) с датой обновления (updated_at)
                    $dueDate = $task['due_date'];
                    $updatedDate = isset($task['updated_at']) ? date('Y-m-d', strtotime($task['updated_at'])) : $today;
                    
                    // Задача выполнена вовремя, если дата обновления <= дата выполнения
                    if ($updatedDate <= $dueDate) {
                        $onTime++;
                    }
                } else {
                    $pending++;
                    // Проверяем просроченные задачи
                    if ($task['due_date'] < $today) {
                        $overdue++;
                    }
                }
                
                // Подсчёт по приоритетам
                $priorityStats[$task['priority']]++;
            }
            
            $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            $onTimeRate = $completed > 0 ? round(($onTime / $completed) * 100, 1) : 0;
            
            // Формируем детальный отчёт
            $report = self::generateEfficiencyReport($tasks, $total, $completed, $pending, $overdue, $onTime, $completionRate, $onTimeRate, $priorityStats, $priorityCompleted, $monthOffset);
            
            return [
                'success' => true,
                'message' => 'Отчёт по эффективности готов',
                'report' => $report,
                'stats' => [
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $pending,
                    'overdue' => $overdue,
                    'on_time' => $onTime,
                    'completion_rate' => $completionRate,
                    'on_time_rate' => $onTimeRate,
                    'priority_stats' => $priorityStats,
                    'priority_completed' => $priorityCompleted
                ],
                'tasks' => $tasks
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при анализе эффективности: ' . $e->getMessage()
            ];
        }
    }
    
    // Генерация текстового отчёта по эффективности
    private static function generateEfficiencyReport($tasks, $total, $completed, $pending, $overdue, $onTime, $completionRate, $onTimeRate, $priorityStats, $priorityCompleted, $monthOffset = 0): string {
        $report = "📊 ОТЧЁТ ПО ЭФФЕКТИВНОСТИ\n\n";
        
        // Общая статистика
        $report .= "📈 ОБЩАЯ СТАТИСТИКА:\n";
        $report .= "• Всего задач: $total\n";
        $report .= "• Выполнено: $completed (" . ($total > 0 ? round(($completed / $total) * 100, 1) : 0) . "%)\n";
        $report .= "• В работе: $pending\n";
        $report .= "• Просрочено: $overdue\n";
        $report .= "• Выполнено вовремя: $onTime из $completed (" . ($completed > 0 ? round(($onTime / $completed) * 100, 1) : 0) . "%)\n\n";
        
        // Статистика по приоритетам
        $report .= "🎯 СТАТИСТИКА ПО ПРИОРИТЕТАМ:\n";
        $priorities = ['high' => 'Высокий', 'medium' => 'Средний', 'low' => 'Низкий'];
        foreach ($priorities as $key => $label) {
            $totalPriority = $priorityStats[$key];
            $completedPriority = $priorityCompleted[$key];
            $rate = $totalPriority > 0 ? round(($completedPriority / $totalPriority) * 100, 1) : 0;
            $report .= "• $label: $completedPriority из $totalPriority выполнено ($rate%)\n";
        }
        $report .= "\n";
        
        // Примеры задач
        $report .= "📝 ПРИМЕРЫ ЗАДАЧ:\n";
        $completedTasks = array_filter($tasks, function($t) { return $t['status'] === 'completed'; });
        $pendingTasks = array_filter($tasks, function($t) { return $t['status'] !== 'completed'; });
        
        if (!empty($completedTasks)) {
            $report .= "\n✅ Выполненные задачи:\n";
            $completedSample = array_slice($completedTasks, 0, 5);
            foreach ($completedSample as $task) {
                $report .= "• " . $task['title'] . " (" . $task['due_date'] . ")\n";
            }
        }
        
        if (!empty($pendingTasks)) {
            $report .= "\n⏳ Задачи в работе:\n";
            $pendingSample = array_slice($pendingTasks, 0, 5);
            foreach ($pendingSample as $task) {
                $status = $task['due_date'] < date('Y-m-d') ? " [ПРОСРОЧЕНО]" : "";
                $report .= "• " . $task['title'] . " (" . $task['due_date'] . ")$status\n";
            }
        }
        
        // Советы
        $report .= "\n💡 СОВЕТЫ ПО УЛУЧШЕНИЮ ЭФФЕКТИВНОСТИ:\n";
        
        if ($completionRate < 50) {
            $report .= "• Ваш процент выполнения задач ниже 50%. Попробуйте ставить более реалистичные цели и разбивать большие задачи на меньшие.\n";
        } elseif ($completionRate < 70) {
            $report .= "• Хороший результат! Для улучшения попробуйте планировать задачи заранее и устанавливать напоминания.\n";
        } else {
            $report .= "• Отличный результат! Вы выполняете большинство задач. Продолжайте в том же духе!\n";
        }
        
        if ($overdue > 0) {
            $report .= "• У вас есть просроченные задачи. Пересмотрите их приоритеты и либо выполните, либо перенесите на более поздний срок.\n";
        }
        
        if ($onTimeRate < 70 && $completed > 0) {
            $report .= "• Многие задачи выполняются с опозданием. Попробуйте более реалистично оценивать время на выполнение задач.\n";
        }
        
        if ($priorityStats['high'] > 0 && $priorityCompleted['high'] < $priorityStats['high']) {
            $report .= "• Обратите внимание на задачи с высоким приоритетом - не все из них выполнены.\n";
        }
        
        // Форматируем название месяца на русском с учётом смещения
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];
        $targetDate = strtotime("$monthOffset months");
        $targetMonth = (int)date('n', $targetDate);
        $targetYear = date('Y', $targetDate);
        $report .= "\n📅 Период: " . $months[$targetMonth] . " " . $targetYear . "\n";
        
        return $report;
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
