<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <title>Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
    :root {
        --tg-theme-bg-color: #000;
        --tg-theme-text-color: #fff;
        --tg-theme-button-color: #212529;
        --tg-theme-button-text-color: #fff;
        --tg-theme-secondary-bg-color: #121212;
        --tg-theme-hint-color: #aaa;
        --tg-theme-link-color: #007bff;
    }

    body {
        background: var(--tg-theme-bg-color);
        color: var(--tg-theme-text-color);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .navbar {
        background: var(--tg-theme-secondary-bg-color) !important;
    }

    .card {
        background: var(--tg-theme-secondary-bg-color);
        border: 1px solid #333;
        margin-bottom: 15px;
    }

    .form-control {
        background: #333;
        border: 1px solid #555;
        color: white;
    }

    .form-control:focus {
        background: #444;
        border-color: #007bff;
        color: white;
    }

    .btn-primary {
        background: var(--tg-theme-button-color);
        border: none;
    }

    .priority-high {
        border-left: 4px solid #dc3545;
    }

    .priority-medium {
        border-left: 4px solid #ffc107;
    }

    .priority-low {
        border-left: 4px solid #28a745;
    }

    .completed {
        opacity: 0.7;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <span class="navbar-brand">📋 Task Manager</span>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#" onclick="showProfile()"><i class="fas fa-user"></i></a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Поиск -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Поиск задач...">
                    <button class="btn btn-primary" onclick="searchTasks()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="btn-group w-100">
                    <button class="btn btn-outline-primary active" onclick="filterTasks('all')">Все</button>
                    <button class="btn btn-outline-primary" onclick="filterTasks('today')">Сегодня</button>
                    <button class="btn btn-outline-primary" onclick="filterTasks('tomorrow')">Завтра</button>
                    <button class="btn btn-outline-primary" onclick="filterTasks('completed')">Выполненные</button>
                </div>
            </div>
        </div>

        <!-- Список задач -->
        <div id="tasksList" class="row"></div>

        <!-- Кнопка добавления -->
        <div class="fixed-bottom p-3">
            <button class="btn btn-primary w-100 rounded-pill" onclick="showAddTaskModal()">
                <i class="fas fa-plus"></i> Добавить задачу
            </button>
        </div>
    </div>

    <!-- Модальное окно добавления задачи -->
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить задачу</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Дата</label>
                                <input type="date" class="form-control" name="due_date" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Время</label>
                                <input type="time" class="form-control" name="due_time" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-6">
                                <label class="form-label">Приоритет</label>
                                <select class="form-select" name="priority">
                                    <option value="low">Низкий</option>
                                    <option value="medium" selected>Средний</option>
                                    <option value="high">Высокий</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Напоминание</label>
                                <select class="form-select" name="reminder">
                                    <option value="none">Не напоминать</option>
                                    <option value="30min">За 30 минут</option>
                                    <option value="5min">За 5 минут</option>
                                    <option value="1min">За 1 минуту</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="addTask()">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно профиля -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Профиль</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="profileStats"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let tg = window.Telegram.WebApp;
    tg.expand();
    tg.enableClosingConfirmation();

    let currentUserId = tg.initDataUnsafe.user.id;
    let currentFilter = 'all';

    // Инициализация
    $(document).ready(function() {
        loadTasks();
        setupDateDefaults();
        // Сохраняем пользователя если нужно
        saveUserIfNeeded();
    });

    function setupDateDefaults() {
        const now = new Date();
        const today = now.toISOString().split('T')[0];
        const time = now.toTimeString().substr(0, 5);

        $('input[name="due_date"]').val(today);
        $('input[name="due_time"]').val(time);
    }

    function saveUserIfNeeded() {
        // Сохраняем пользователя в базу для напоминаний
        $.post('handler.php?action=save_user', {
            user_id: currentUserId,
            first_name: tg.initDataUnsafe.user.first_name,
            last_name: tg.initDataUnsafe.user.last_name,
            username: tg.initDataUnsafe.user.username,
            chat_id: tg.initDataUnsafe.user.id // Для WebApp chat_id = user_id
        });
    }

    function loadTasks(filter = 'all') {
        $.get(`handler.php?action=get&user_id=${currentUserId}&filter=${filter}`, function(data) {
            const tasks = JSON.parse(data).tasks;
            renderTasks(tasks);
        });
    }

    function renderTasks(tasks) {
        const container = $('#tasksList');
        container.empty();

        if (tasks.length === 0) {
            container.html('<div class="col-12 text-center text-muted">Нет задач</div>');
            return;
        }

        tasks.forEach(task => {
            const taskElement = `
                <div class="col-12">
                    <div class="card priority-${task.priority} ${task.status === 'completed' ? 'completed' : ''}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title">${escapeHtml(task.title)}</h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-white" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark">
                                        <li><a class="dropdown-item" href="#" onclick="toggleTaskStatus(${task.id}, '${task.status}')">
                                            ${task.status === 'completed' ? 'Вернуть' : 'Выполнить'}
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="editTask(${task.id})">Редактировать</a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteTask(${task.id})">Удалить</a></li>
                                    </ul>
                                </div>
                            </div>
                            <p class="card-text text-muted">${escapeHtml(task.description || 'Без описания')}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> ${task.due_date} 
                                    <i class="fas fa-clock ms-2"></i> ${task.due_time}
                                </small>
                                <span class="badge bg-${getPriorityBadge(task.priority)}">
                                    ${getPriorityText(task.priority)}
                                </span>
                            </div>
                            ${task.reminder !== 'none' ? 
                                `<small class="text-info"><i class="fas fa-bell"></i> Напоминание: ${getReminderText(task.reminder)}</small>` : ''}
                        </div>
                    </div>
                </div>
            `;
            container.append(taskElement);
        });
    }

    function escapeHtml(unsafe) {
        return unsafe?.replace(/[&<"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '"': '&quot;',
            "'": '&#039;'
        } [m])) || '';
    }

    function getPriorityBadge(priority) {
        const badges = {
            high: 'danger',
            medium: 'warning',
            low: 'success'
        };
        return badges[priority] || 'secondary';
    }

    function getPriorityText(priority) {
        const texts = {
            high: 'Высокий',
            medium: 'Средний',
            low: 'Низкий'
        };
        return texts[priority] || 'Неизвестно';
    }

    function getReminderText(reminder) {
        const texts = {
            '30min': '30 мин',
            '5min': '5 мин',
            '1min': '1 мин'
        };
        return texts[reminder] || 'Неизвестно';
    }

    function showAddTaskModal() {
        $('#taskForm')[0].reset();
        setupDateDefaults();
        new bootstrap.Modal('#addTaskModal').show();
    }

    function addTask() {
        const formData = $('#taskForm').serializeArray();
        const taskData = {};
        formData.forEach(field => taskData[field.name] = field.value);

        $.post('handler.php?action=add', {
            user_id: currentUserId,
            ...taskData
        }, function(response) {
            const result = JSON.parse(response);
            if (result.success) {
                $('#addTaskModal').modal('hide');
                loadTasks(currentFilter);
                tg.showPopup({
                    title: 'Успех',
                    message: 'Задача добавлена'
                });
            }
        });
    }

    function deleteTask(taskId) {
        if (confirm('Удалить задачу?')) {
            $.post('handler.php?action=delete', {
                user_id: currentUserId,
                task_id: taskId
            }, function() {
                loadTasks(currentFilter);
            });
        }
    }

    function toggleTaskStatus(taskId, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        $.post('handler.php?action=update', {
            user_id: currentUserId,
            task_id: taskId,
            status: newStatus
        }, function() {
            loadTasks(currentFilter);
        });
    }

    function filterTasks(filter) {
        currentFilter = filter;
        $('.btn-outline-primary').removeClass('active');
        $(`.btn-outline-primary:contains(${getFilterText(filter)})`).addClass('active');
        loadTasks(filter);
    }

    function getFilterText(filter) {
        const texts = {
            all: 'Все',
            today: 'Сегодня',
            tomorrow: 'Завтра',
            completed: 'Выполненные'
        };
        return texts[filter] || filter;
    }

    function searchTasks() {
        const query = $('#searchInput').val();
        if (query.length > 2) {
            $.get(`handler.php?action=search&user_id=${currentUserId}&q=${encodeURIComponent(query)}`, function(data) {
                const tasks = JSON.parse(data).tasks;
                renderTasks(tasks);
            });
        } else if (query.length === 0) {
            loadTasks(currentFilter);
        }
    }

    function showProfile() {
        $.get(`handler.php?action=stats&user_id=${currentUserId}`, function(data) {
            const stats = JSON.parse(data).stats;
            let html = `
                <div class="text-center mb-4">
                    <h4>${tg.initDataUnsafe.user.first_name} ${tg.initDataUnsafe.user.last_name}</h4>
                    <p class="text-muted">@${tg.initDataUnsafe.user.username}</p>
                </div>
                <div class="row text-center">
            `;

            if (stats.pending) {
                html += `
                    <div class="col-4">
                        <div class="bg-primary rounded p-3">
                            <h3>${stats.pending.count}</h3>
                            <small>В работе</small>
                        </div>
                    </div>
                `;
            }

            if (stats.completed) {
                html += `
                    <div class="col-4">
                        <div class="bg-success rounded p-3">
                            <h3>${stats.completed.count}</h3>
                            <small>Выполнено</small>
                        </div>
                    </div>
                `;
            }

            html += '</div>';
            $('#profileStats').html(html);
            new bootstrap.Modal('#profileModal').show();
        });
    }
    </script>
</body>

</html>