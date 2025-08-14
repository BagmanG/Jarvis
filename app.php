<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой TO DO список</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 bg-dark text-white sidebar">
                <div class="sidebar-sticky pt-4">
                    <h1 class="h4 text-center mb-4">Мои задачи</h1>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-filter="today">
                                <i class="fas fa-calendar-day me-2"></i>Сегодня
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-filter="tomorrow">
                                <i class="fas fa-calendar-alt me-2"></i>Завтра
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-filter="week">
                                <i class="fas fa-calendar-week me-2"></i>На неделю
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-filter="all">
                                <i class="fas fa-tasks me-2"></i>Все задачи
                            </a>
                        </li>
                    </ul>
                    <div class="mt-5 px-3">
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success" role="progressbar" id="progress-bar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted" id="progress-text">Завершено 0 из 0 задач</small>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 id="current-filter">Сегодня</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                            <i class="fas fa-plus me-2"></i>Добавить задачу
                        </button>
                    </div>

                    <!-- Search box -->
                    <div class="input-group mb-4">
                        <input type="text" class="form-control" id="search-input" placeholder="Поиск задач...">
                        <button class="btn btn-outline-secondary" type="button" id="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <!-- Tasks list -->
                    <div class="row" id="tasks-container">
                        <!-- Tasks will be loaded here -->
                    </div>

                    <!-- Empty state -->
                    <div class="text-center py-5" id="empty-state">
                        <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Нет задач</h4>
                        <p class="text-muted">Нажмите кнопку "Добавить задачу", чтобы создать новую</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTaskModalLabel">Добавить новую задачу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="task-form">
                        <div class="mb-3">
                            <label for="task-title" class="form-label">Название задачи</label>
                            <input type="text" class="form-control" id="task-title" required>
                        </div>
                        <div class="mb-3">
                            <label for="task-description" class="form-label">Описание</label>
                            <textarea class="form-control" id="task-description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="task-date" class="form-label">Срок выполнения</label>
                            <select class="form-select" id="task-date" required>
                                <option value="today">Сегодня</option>
                                <option value="tomorrow">Завтра</option>
                                <option value="week">На неделю</option>
                                <option value="custom">Выбрать дату</option>
                            </select>
                        </div>
                        <div class="mb-3" id="custom-date-container" style="display: none;">
                            <label for="task-custom-date" class="form-label">Выберите дату</label>
                            <input type="date" class="form-control" id="task-custom-date">
                        </div>
                        <div class="mb-3">
                            <label for="task-priority" class="form-label">Приоритет</label>
                            <select class="form-select" id="task-priority">
                                <option value="low">Низкий</option>
                                <option value="medium" selected>Средний</option>
                                <option value="high">Высокий</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="save-task">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Details Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskDetailsModalLabel">Детали задачи</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 id="detail-title" class="mb-3"></h4>
                    <p id="detail-description" class="text-muted mb-4"></p>
                    <div class="mb-3">
                        <strong>Срок выполнения:</strong>
                        <span id="detail-date" class="ms-2"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Приоритет:</strong>
                        <span id="detail-priority" class="ms-2"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Статус:</strong>
                        <span id="detail-status" class="ms-2"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="delete-task">Удалить</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-primary" id="toggle-status">Отметить как выполненное</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>