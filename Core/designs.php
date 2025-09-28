<?php
// designs.php — шаблоны визуализации задач

function render_calendar_design($tasks) {
    // Классический календарь
    ob_start();
    ?>
    <div class="bg-secondary rounded-2xl shadow-soft p-4">
        <div class="flex items-center justify-between">
            <button class="p-2 rounded-xl hover:bg-white/5" id="prevMonth"><i class="fa-solid fa-angle-left"></i></button>
            <div class="text-sm text-hint" id="monthLabel"><?= date('M Y') ?></div>
            <button class="p-2 rounded-xl hover:bg-white/5" id="nextMonth"><i class="fa-solid fa-angle-right"></i></button>
        </div>
        <div class="mt-3 grid grid-cols-7 text-center text-xs text-hint">
            <div>Пн</div><div>Вт</div><div>Ср</div><div>Чт</div><div>Пт</div><div>Сб</div><div>Вс</div>
        </div>
        <div id="calendarGrid" class="mt-2 grid grid-cols-7 gap-1"></div>
    </div>
    
    <!-- События под календарём -->
    <div class="bg-secondary rounded-2xl p-4">
        <div class="flex items-center gap-2 text-sm text-hint mb-2">
            <span class="w-2 h-2 bg-red-500 rounded-full"></span> События
        </div>
        <div id="eventCard" class="bg-black/20 rounded-2xl p-3 flex items-center justify-between">
            <div>
                <div class="font-medium" id="eventTitle">Нет задач</div>
                <div class="text-xs text-hint" id="eventDate">—</div>
            </div>
            <div class="text-xs font-medium" id="eventTime"></div>
        </div>
    </div>

    <!-- Мои задачи -->
    <div class="bg-secondary rounded-2xl p-2">
        <div class="p-3">
            <h2 class="text-lg font-semibold mb-2">Мои задачи</h2>
            <div class="space-y-3">
                <?php if (empty($tasks)): ?>
                    <div class="text-sm text-hint">Нет задач</div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-row group flex items-start gap-3 px-3 py-3 rounded-2xl hover:bg-white/5">
                            <button class="mt-1 w-5 h-5 rounded-full border border-white/30 flex items-center justify-center">
                                <?= $task['status'] === 'completed' ? '<i class="fa-solid fa-check text-xs"></i>' : '' ?>
                            </button>
                            <div class="flex-1">
                                <div class="font-medium"><?= htmlspecialchars($task['title']) ?></div>
                                <?php if (!empty($task['description'])): ?>
                                    <div class="text-sm text-hint mt-1"><?= htmlspecialchars($task['description']) ?></div>
                                <?php endif; ?>
                                <div class="mt-2 flex items-center justify-between text-xs text-hint">
                                    <div><i class="fa-solid fa-calendar"></i> <?= $task['due_date'] ?> <i class="fa-solid fa-clock ml-2"></i> <?= $task['due_time'] ?></div>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full <?= $task['priority'] === 'high' ? 'bg-red-500' : ($task['priority'] === 'medium' ? 'bg-yellow-500' : 'bg-emerald-500') ?>"></span>
                                        <?= $task['priority'] === 'high' ? 'Высокий' : ($task['priority'] === 'low' ? 'Низкий' : 'Средний') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_todo_list_design($tasks) {
    // Простой список задач
    ob_start();
    ?>
    <div class="bg-secondary rounded-2xl p-4">
        <h2 class="text-lg font-semibold mb-4">ToDo List</h2>
        <div class="space-y-3">
            <?php if (empty($tasks)): ?>
                <div class="text-sm text-hint">Нет задач</div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-white/5">
                        <input type="checkbox" <?= $task['status'] === 'completed' ? 'checked' : '' ?> disabled class="mt-1">
                        <div class="flex-1">
                            <div class="font-medium <?= $task['status'] === 'completed' ? 'line-through opacity-70' : '' ?>">
                                <?= htmlspecialchars($task['title']) ?>
                            </div>
                            <?php if (!empty($task['description'])): ?>
                                <div class="text-sm text-hint mt-1"><?= htmlspecialchars($task['description']) ?></div>
                            <?php endif; ?>
                            <div class="text-xs text-hint mt-1">
                                <i class="fa-solid fa-calendar"></i> <?= $task['due_date'] ?> 
                                <i class="fa-solid fa-clock ml-2"></i> <?= $task['due_time'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_block_planner_design($tasks) {
    // Блочный планировщик (примерно как на картинке)
    ob_start();
    ?>
    <div class="bg-secondary rounded-2xl p-4">
        <h2 class="text-lg font-semibold mb-4">Планировщик (блочный)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (empty($tasks)): ?>
                <div class="text-sm text-hint">Нет задач</div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="bg-gradient-to-br from-blue-500/20 to-purple-500/20 border border-blue-400/30 rounded-xl p-4 hover:shadow-lg transition-shadow">
                        <div class="font-semibold text-white mb-2"><?= htmlspecialchars($task['title']) ?></div>
                        <?php if (!empty($task['description'])): ?>
                            <div class="text-sm text-hint mb-2"><?= htmlspecialchars($task['description']) ?></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between text-xs text-hint">
                            <div>
                                <i class="fa-solid fa-calendar"></i> <?= $task['due_date'] ?>
                            </div>
                            <div>
                                <i class="fa-solid fa-clock"></i> <?= $task['due_time'] ?>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs px-2 py-1 rounded-full <?= $task['priority'] === 'high' ? 'bg-red-500/30 text-red-300' : ($task['priority'] === 'medium' ? 'bg-yellow-500/30 text-yellow-300' : 'bg-emerald-500/30 text-emerald-300') ?>">
                                <?= $task['priority'] === 'high' ? 'Высокий' : ($task['priority'] === 'low' ? 'Низкий' : 'Средний') ?>
                            </span>
                            <span class="text-xs px-2 py-1 rounded-full <?= $task['status'] === 'completed' ? 'bg-green-500/30 text-green-300' : 'bg-orange-500/30 text-orange-300' ?>">
                                <?= $task['status'] === 'completed' ? 'Выполнено' : 'В работе' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Для удобства расширения — массив доступных дизайнов
$DESIGN_TEMPLATES = [
    1 => [
        'name' => 'Календарь',
        'render' => 'render_calendar_design',
    ],
    2 => [
        'name' => 'ToDo List',
        'render' => 'render_todo_list_design',
    ],
    3 => [
        'name' => 'Планировщик (блочный)',
        'render' => 'render_block_planner_design',
    ],
];
