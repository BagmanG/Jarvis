<?php
// designs.php — шаблоны визуализации задач

function render_calendar_design($tasks) {
    // Классический календарь (заглушка, заменить на ваш текущий рендер)
    ob_start();
    ?>
    <div class="calendar-design">
        <h2>Календарь</h2>
        <!-- Здесь ваш текущий календарь -->
        <?php foreach ($tasks as $task): ?>
            <div><?= htmlspecialchars($task['title']) ?> (<?= htmlspecialchars($task['date']) ?>)</div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function render_todo_list_design($tasks) {
    // Простой список задач
    ob_start();
    ?>
    <div class="todo-list-design">
        <h2>ToDo List</h2>
        <ul>
            <?php foreach ($tasks as $task): ?>
                <li>
                    <input type="checkbox" <?= $task['done'] ? 'checked' : '' ?> disabled>
                    <?= htmlspecialchars($task['title']) ?>
                    <span style="color: #888; font-size: 0.9em;">(<?= htmlspecialchars($task['date']) ?>)</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function render_block_planner_design($tasks) {
    // Блочный планировщик (примерно как на картинке)
    ob_start();
    ?>
    <div class="block-planner-design">
        <h2>Планировщик (блочный)</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($tasks as $task): ?>
                <div style="background: #e3eaff; border-radius: 8px; padding: 10px; min-width: 180px; max-width: 220px; box-shadow: 0 2px 6px #0001;">
                    <strong><?= htmlspecialchars($task['title']) ?></strong><br>
                    <span style="color: #888; font-size: 0.9em;"><?= htmlspecialchars($task['date']) ?></span><br>
                    <span><?= htmlspecialchars($task['description'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
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
