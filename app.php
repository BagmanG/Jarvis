<?php
require_once __DIR__ . '/src/Support/helpers.php';
date_default_timezone_set(config('app.timezone', 'UTC'));
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="theme-color" content="#0a84ff">
    <title><?= htmlspecialchars(config('app.name', 'Telegram Calendar Mini App'), ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css?v=1.0.2">
</head>
<body>
<div id="app" class="app-shell">
    <div id="loaderScreen" class="loader-screen">
        <div class="loader-orb"></div>
        <div class="loader-copy">
            <h1>Загружаем ваши данные....</h1> 
            <p>Секунду, собираем календарь, профиль и ваши настройки.</p>
        </div>
        <button id="retryBootBtn" class="ios-button ios-button-primary hidden">Повторить</button>
    </div>

    <div id="mainView" class="main-view hidden">
        <header class="glass-header">
            <div class="header-top-row">
                <button id="todayButton" class="ghost-pill">Сегодня</button>
                <div class="header-actions">
                    <button id="monthPickerButton" class="icon-button icon-button-wide">
                        <span id="monthTitleText">Апрель 2026</span>
                    </button>
                    <button id="profileButton" class="avatar-button" aria-label="Открыть профиль"></button>
                </div>
            </div>
            <div class="header-navigation-row">
                <div class="section-heading">Ваши задачи</div>
                <div class="nav-inline-actions">
                    <button id="prevMonthButton" class="icon-button small">‹</button>
                    <button id="nextMonthButton" class="icon-button small">›</button>
                </div>
            </div>
        </header>

        <main class="content-area">
            <section class="calendar-panel card enter-up">
                <div class="calendar-stage">
                    <div class="weekdays weekdays-inline" id="weekdayRow"></div>
                    <div id="calendarGrid" class="calendar-grid"></div>
                </div>
            </section>

            <section class="agenda-panel card enter-up enter-up-delay">
                <div class="agenda-header">
                    <div>
                        <span class="eyebrow">Выбранная дата</span>
                        <h2 id="selectedDateTitle">Сегодня</h2>
                        <p id="selectedDateMeta" class="muted">0 задач</p>
                    </div>
                    <button id="addTaskButton" class="fab-mini">＋</button>
                </div>

                <div id="taskSkeleton" class="task-skeleton-list hidden"></div>
                <div id="tasksList" class="tasks-list"></div>
                <div id="emptyState" class="empty-state hidden">
                    <div class="empty-emoji">✦</div>
                    <h3>На этот день задач нет</h3>
                    <p>Создайте первую задачу — она сразу появится в календарной сетке.</p>
                    <button id="emptyCreateButton" class="ios-button ios-button-primary">Создать задачу</button>
                </div>
            </section>
        </main>

        <nav class="bottom-bar">
            <button class="tab-button active" data-tab="calendar">
                <span>Календарь</span>
            </button>
            <button class="tab-button accent-center" id="bottomAddButton">
                <span>＋</span>
            </button>
            <button class="tab-button" data-action="profile">
                <span>Профиль</span>
            </button>
        </nav>
    </div>

    <div id="toastStack" class="toast-stack"></div>

    <div id="sheetBackdrop" class="sheet-backdrop hidden"></div>

    <section id="taskSheet" class="bottom-sheet hidden" aria-hidden="true">
        <div class="sheet-grabber"></div>
        <div class="sheet-header">
            <button data-close-sheet class="sheet-link">Отмена</button>
            <h3 id="taskSheetTitle">Новая задача</h3>
            <button id="saveTaskButton" class="sheet-link accent">Сохранить</button>
        </div>
        <div class="sheet-content">
            <div class="ios-input-group">
                <input id="taskTitleInput" class="ios-input primary" maxlength="150" placeholder="Название задачи">
                <textarea id="taskDescriptionInput" class="ios-input textarea" maxlength="2000" rows="4" placeholder="Описание"></textarea>
            </div>

            <div class="ios-panel-list">
                <label class="ios-row">
                    <span>На весь день</span>
                    <input id="taskAllDayInput" type="checkbox" class="ios-switch">
                </label>
                <label class="ios-row">
                    <span>Дата</span>
                    <input id="taskDateInput" type="date" class="ios-inline-input">
                </label>
                <label class="ios-row task-time-row">
                    <span>Начало</span>
                    <input id="taskTimeStartInput" type="time" class="ios-inline-input">
                </label>
                <label class="ios-row task-time-row">
                    <span>Конец</span>
                    <input id="taskTimeEndInput" type="time" class="ios-inline-input">
                </label>
            </div>

            <div class="ios-panel-list">
                <label class="ios-row">
                    <span>Приоритет</span>
                    <select id="taskPriorityInput" class="ios-inline-input">
                        <option value="low">Низкий</option>
                        <option value="medium">Средний</option>
                        <option value="high">Высокий</option>
                    </select>
                </label>
                <label class="ios-row">
                    <span>Статус</span>
                    <select id="taskStatusInput" class="ios-inline-input">
                        <option value="active">Активна</option>
                        <option value="completed">Выполнена</option>
                        <option value="archived">Архив</option>
                    </select>
                </label>
                <label class="ios-row">
                    <span>Напомнить</span>
                    <select id="taskReminderInput" class="ios-inline-input">
                        <option value="360">За 6 часов</option>
                        <option value="60">За 1 час</option>
                        <option value="30">За 30 минут</option>
                        <option value="15">За 15 минут</option>
                        <option value="5" selected>За 5 минут</option>
                    </select>
                </label>
            </div>

            <div class="sheet-section">
                <div class="section-title">Цвет задачи</div>
                <div id="taskColorPalette" class="color-palette"></div>
            </div>

            <div class="sheet-section destructive-zone">
                <button id="duplicateTaskButton" class="ios-button hidden">Дублировать</button>
                <button id="completeTaskButton" class="ios-button hidden">Отметить выполненной</button>
                <button id="deleteTaskButton" class="ios-button ios-button-danger hidden">Удалить</button>
            </div>
        </div>
    </section>

    <section id="profileSheet" class="bottom-sheet hidden profile-sheet" aria-hidden="true">
        <div class="sheet-grabber"></div>
        <div class="sheet-header">
            <button data-close-sheet class="sheet-link">Закрыть</button>
            <h3>Профиль</h3>
            <button id="saveProfileButton" class="sheet-link accent">Сохранить</button>
        </div>
        <div class="sheet-content profile-content">
            <div class="profile-hero card-soft">
                <div id="profileAvatarLarge" class="profile-avatar-large"></div>
                <div class="profile-meta">
                    <input id="profileDisplayNameInput" class="profile-name-input" maxlength="120" placeholder="Ваше имя">
                    <div id="profileTelegramLine" class="muted mono">@username</div>
                </div>
            </div>

            <div class="sheet-section">
                <div class="section-title">Аватар</div>
                <div class="profile-avatar-hint muted">
                    Аватар автоматически берётся из профиля Telegram. Если фото недоступно, показывается первая буква имени.
                </div>
            </div>

            <div class="sheet-section">
                <div class="section-title">Тема</div>
                <div id="themeModeControl" class="segmented-control"></div>
            </div>

            <div class="sheet-section">
                <div class="section-title">Акцент</div>
                <div id="accentPalette" class="color-palette large"></div>
            </div>

            <div class="sheet-section">
                <div class="section-title">Первый день недели</div>
                <div id="weekStartControl" class="segmented-control"></div>
            </div>
        </div>
    </section>

    <section id="monthPickerSheet" class="bottom-sheet hidden" aria-hidden="true">
        <div class="sheet-grabber"></div>
        <div class="sheet-header">
            <button data-close-sheet class="sheet-link">Закрыть</button>
            <h3>Быстрый переход</h3>
            <button id="jumpTodayButton" class="sheet-link accent">Сегодня</button>
        </div>
        <div class="sheet-content">
            <div class="picker-year-bar">
                <button id="pickerPrevYear" class="icon-button small">‹</button>
                <strong id="pickerYearLabel">2026</strong>
                <button id="pickerNextYear" class="icon-button small">›</button>
            </div>
            <div id="monthPickerGrid" class="month-picker-grid"></div>
        </div>
    </section>
</div>
<script>
window.APP_CONFIG = {
    apiBase: 'api/v1',
    debugMode: <?= config('app.debug') ? 'true' : 'false' ?>,
    defaultTimezone: <?= json_encode(config('app.timezone', 'UTC'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script type="module" src="assets/js/core/app.js?v=1.0.2"></script>
</body>
</html>
