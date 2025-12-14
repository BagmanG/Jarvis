<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <title>Джарвис — Task Manager</title>
    <!-- Запрет зума на мобилках -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#1E1E2F',
                    secondary: '#2A2A3D',
                    accent: '#4F46E5',
                    hint: '#9AA4B2',
                    card: '#232333',
                    textmain: '#FFFFFF'
                },
                boxShadow: {
                    soft: '0 8px 24px rgba(0,0,0,.12)'
                },
                borderRadius: {
                    xl2: '1rem'
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    html,
    body {
        height: 100%
    }

    body {
        background: #1E1E2F;
        color: #FFFFFF;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Inter, system-ui, sans-serif
    }

    .scrollbar-none::-webkit-scrollbar {
        display: none
    }

    .menu-open .task-menu {
        display: block
    }
    .hour-row {
  height: 64px;
  border-top: 1px solid rgba(255,255,255,0.04);
}

.day-column {
  position: relative;
  border-left: 1px solid rgba(255,255,255,0.04);
}

.task-tile {
  position: absolute;
  background: #6EC1FF;
  color: #0A2540;
  border-radius: 10px;
  padding: 6px 8px;

  font-size: 12px;
  line-height: 1.3;

  cursor: pointer;

  height: 60px;            /* 🔒 фиксированная высота */
  overflow: hidden;        /* 🔒 текст не влияет */
  white-space: normal;     /* перенос строк */
  word-break: break-word;

  box-sizing: border-box;
}


.day-column.today {
  background: rgba(110,193,255,0.08);
}
.bgg{
    background-color: #1F2025;
}
    </style>
</head>

<body class="min-h-screen">
    <!-- Top bar -->
    <header class="sticky top-0 z-30 backdrop-blur bg-black/10" style="background-color: #000000;">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
            <!-- Кнопку-стрелку убрали -->
            <h1 class="text-xl font-semibold">Джарвис</h1>
            <div class="ml-auto flex items-center gap-2">
                <div class="relative">
                    <input id="searchInput"
                        class="peer w-56 md:w-96 bgg rounded-xl pl-10 pr-3 py-2 outline-none placeholder-hint text-sm"
                        placeholder="Поиск задач..." />
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-hint peer-focus:text-white"></i>
                </div>
                <!-- Кнопки Профиль и Плюс в шапке удалены -->
            </div>
        </div>
    </header>

    <main class="h-[calc(100vh-64px)] overflow-hidden">

  <section class="relative h-full bgg">
        <div class="flex items-center justify-between px-4 py-2 bgg border-b border-white/5">
  <div class="flex items-center gap-2">
    <button id="prevWeek" class="px-2 py-1 rounded hover:bg-white/10">←</button>
    <button id="nextWeek" class="px-2 py-1 rounded hover:bg-white/10">→</button>
  </div>

  <div id="weekLabel" class="text-sm text-hint"></div>
</div>
    <!-- Header days -->
    <div class="grid grid-cols-[64px_repeat(7,1fr)] sticky top-0 z-20 bgg border-b border-white/5">
      <div></div>
      <div class="text-center py-2 text-sm text-hint">Пн</div>
      <div class="text-center py-2 text-sm text-hint">Вт</div>
      <div class="text-center py-2 text-sm text-hint">Ср</div>
      <div class="text-center py-2 text-sm text-hint">Чт</div>
      <div class="text-center py-2 text-sm text-hint">Пт</div>
      <div class="text-center py-2 text-sm text-hint">Сб</div>
      <div class="text-center py-2 text-sm text-hint">Вс</div>
    </div>

    <!-- Grid -->
    <div id="weekGrid" class="relative overflow-y-auto h-full">
      <div class="grid grid-cols-[64px_repeat(7,1fr)]">
        <!-- часы -->
        <div id="timeColumn"></div>

        <!-- дни -->
        <div class="col-span-7 relative" id="tasksLayer"></div>
      </div>
    </div>

  </section>
</main>


    <!-- Нижний бар фильтров удалён полностью -->

    <!-- Плавающая кнопка добавления (оставили, непрозрачная) -->
    <button
        class="fixed bottom-6 right-5 w-14 h-14 rounded-full text-white shadow-soft flex items-center justify-center hover:opacity-90"
        style="background-color: #3f3f3f;border-radius: 10px;"
        onclick="showAddTaskModal()" aria-label="Добавить задачу">
        <i class="fa-solid fa-plus"></i>
    </button>

    <!-- Modal Add/Edit -->
    <div id="taskModal" class="hidden fixed inset-0 z-50 items-end md:items-center justify-center">
        <div class="absolute inset-0 bg-black/60" onclick="closeTaskModal()"></div>
        <div class="relative w-full md:w-[520px] bgg rounded-t-2xl md:rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 id="modalTitle" class="text-lg font-semibold">Добавить задачу</h3>
                <button class="p-2 rounded-xl hover:bg-white/5" onclick="closeTaskModal()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="taskForm" class="space-y-3">
                <input type="hidden" name="task_id" />
                <div>
                    <label class="text-sm text-hint">Название</label>
                    <input name="title" class="mt-1 w-full bg-black/30 rounded-xl px-3 py-2 outline-none" required />
                </div>
                <div>
                    <label class="text-sm text-hint">Описание</label>
                    <textarea name="description" rows="3"
                        class="mt-1 w-full bg-black/30 rounded-xl px-3 py-2 outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-hint">Дата</label>
                        <input type="date" name="due_date"
                            class="mt-1 w-full bg-black/30 rounded-xl px-3 py-2 outline-none" required />
                    </div>
                    <div>
                        <label class="text-sm text-hint">Время</label>
                        <input type="time" name="due_time"
                            class="mt-1 w-full bg-black/30 rounded-xl px-3 py-2 outline-none" required />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-hint">Приоритет</label>
                        <select name="priority" class="mt-1 w-full bg-black/30 rounded-xl px-3 py-2 outline-none">
                            <option value="low">Низкий</option>
                            <option value="medium" selected>Средний</option>
                            <option value="high">Высокий</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-hint">Напоминание</label>
                        <select name="reminder" class="mt-1 w-full bg-black/30 rounded-xl px-3 py-2 outline-none">
                            <option value="none">Не напоминать</option>
                            <option value="30min">За 30 минут</option>
                            <option value="5min">За 5 минут</option>
                            <option value="1min">За 1 минуту</option>
                        </select>
                    </div>
                </div>
            </form>
            <div class="mt-4 flex items-center justify-between">
                <button id="deleteBtn" class="hidden text-red-400 hover:text-red-300" onclick="deleteFromModal()"><i
                        class="fa-solid fa-trash mr-2"></i>Удалить</button>
                <div class="ml-auto space-x-2">
                    <button class="px-4 py-2 rounded-xl bg-white/10" onclick="closeTaskModal()">Отмена</button>
                    <button id="saveBtn" class="px-4 py-2 rounded-xl bg-accent text-white"
                        onclick="submitTask()">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Profile Modal (оставим на будущее, кнопка вызова скрыта) -->
    <div id="profileModal" class="hidden fixed inset-0 z-50 items-center justify-center">
        <div class="absolute inset-0 bg-black/60" onclick="closeProfile()"></div>
        <div class="relative w-full md:w-[520px] bgg rounded-2xl p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold">Профиль</h3>
                <button class="p-2 rounded-xl hover:bg-white/5" onclick="closeProfile()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="profileStats" class="space-y-3 text-sm"></div>
        </div>
    </div>

    <script>
    // Telegram
    let tg = window.Telegram?.WebApp || {
        initDataUnsafe: {
            user: {}
        },
        expand: () => {},
        enableClosingConfirmation: () => {}
    };
    tg.expand();
    <?php
      if(isset($_GET['flavor']) && $_GET['flavor']==='test'){
        echo "let currentUserId = 1;";
      } else {
        echo "let currentUserId = (tg.initDataUnsafe && tg.initDataUnsafe.user && tg.initDataUnsafe.user.id) || 1;";
      }
    ?>

    // State
    let selectedDate = getUTCDateWithOffset();
    let lastLoadedTasks = [];
    let openMenuId = null; // для меню задачи

    // Helpers
    const fmt = (d) => d.toISOString().slice(0, 10);
    const pad = (n) => n < 10 ? '0' + n : '' + n;
    const months = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];

    function escapeHtml(unsafe) {
        return $('<div/>').text(unsafe || '').html();
    }

    function getUTCDateWithOffset() {
        const now = new Date();
        // Получаем текущее время в UTC и добавляем 3 часа (UTC+3)
        const utcPlus3 = new Date(now.getTime() + (3 * 60 * 60 * 1000));
        return utcPlus3;
    }

    function getTodayUTCPLus3() {
        return fmt(getUTCDateWithOffset());
    }

    function getTomorrowUTCPLus3() {
        const tomorrow = new Date(getUTCDateWithOffset().getTime() + 86400000);
        return fmt(tomorrow);
    }
    function renderHours() {
  const col = $('#timeColumn');
  col.empty();
  for (let h = 7; h <= 23; h++) {
    col.append(`
      <div class="hour-row text-xs text-hint text-right pr-2 pt-1">
        ${h}:00
      </div>
    `);
  }
}

function prevWeek() {
  selectedDate.setDate(selectedDate.getDate() - 7);
  loadTasks();
}

function nextWeek() {
  selectedDate.setDate(selectedDate.getDate() + 7);
  loadTasks();
}

$('#prevWeek').on('click', prevWeek);
$('#nextWeek').on('click', nextWeek);

function updateWeekLabel(start) {
  const end = new Date(start);
  end.setDate(end.getDate() + 6);

  $('#weekLabel').text(
    `${start.toLocaleDateString()} – ${end.toLocaleDateString()}`
  );
}


function renderWeekView(tasks) {
  renderHours();
  
  const layer = $('#tasksLayer');
  layer.empty();

  const startOfWeek = getStartOfWeek(selectedDate);
      updateWeekLabel(startOfWeek);

      const today = new Date();
today.setHours(0,0,0,0);

  for (let day = 0; day < 7; day++) {
    const colDate = new Date(startOfWeek);
    colDate.setDate(colDate.getDate() + day);

    const isToday = colDate.getTime() === today.getTime();

    layer.append(`
    <div class="day-column absolute top-0 bottom-0 ${isToday ? 'today' : ''}"
        style="left:${day * 100 / 7}%; width:${100 / 7}%">
    </div>
    `);
  }
  
  tasks.forEach(t => {
    placeTask(t, startOfWeek);
  });
}
$(document).on('keydown', e => {
  if (e.key === 'ArrowLeft') prevWeek();
  if (e.key === 'ArrowRight') nextWeek();
});

function parseLocalDate(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number);
  return new Date(y, m - 1, d, 0, 0, 0, 0);
}
function placeTask(task, startOfWeek) {
  const taskDate = parseLocalDate(task.due_date);
    const dayIndex =
  Math.floor(
    (taskDate - startOfWeek) / 86400000
  );
  if (dayIndex < 0 || dayIndex > 6) return;

  const [h, m] = task.due_time.split(':').map(Number);
  const top = ((h - 7) * 60 + m) * (64 / 60);
  const height = 60;

  const tile = $(`
  <div class="task-tile"
    style="
      top:${top}px;
      left:${dayIndex * 100 / 7}%;
      width:${100 / 7 - 1}%;
    ">
    <div class="font-medium">${escapeHtml(task.title)}</div>
    <div class="opacity-70 text-[10px]">${task.due_time}</div>
  </div>
`);
tile.attr('title', task.title);
$('#tasksLayer').append(tile);


const minHeight = 64;

  tile.on('click', () => openEditTask(task));
  $('#tasksLayer').append(tile);
}
function getStartOfWeek(date) {
  const d = new Date(date);
  const day = (d.getDay() + 6) % 7;
  d.setDate(d.getDate() - day);
  d.setHours(0, 0, 0, 0);
  return d;
}

function openEditTask(task) {
  $('#modalTitle').text('Изменить задачу');
  $('#deleteBtn').removeClass('hidden');
  openTaskModal();
  $('input[name="task_id"]').val(task.id);
  $('input[name="title"]').val(task.title);
  $('textarea[name="description"]').val(task.description || '');
  $('input[name="due_date"]').val(task.due_date);
  $('input[name="due_time"]').val(task.due_time);
  $('select[name="priority"]').val(task.priority || 'medium');
  $('select[name="reminder"]').val(task.reminder || 'none');
}
    // Init
    $(function() {
        setupDateDefaults();
        saveUserIfNeeded();
        bindUI();
        loadTasks();
        renderCalendar();

        // клик вне открытого меню — закрыть его
        $(document).on('click', function(e) {
            const $menu = $('.task-menu:visible');
            if (!$menu.length) return;
            const isInside = $(e.target).closest('.task-menu, .task-menu-btn').length > 0;
            if (!isInside) {
                hideTaskMenus();
            }
        });
    });

    function bindUI() {
        $('#prevMonth').on('click', () => {
            selectedDate.setMonth(selectedDate.getMonth() - 1);
            renderCalendar();
        });
        $('#nextMonth').on('click', () => {
            selectedDate.setMonth(selectedDate.getMonth() + 1);
            renderCalendar();
        });
        $('#searchInput').on('input', searchTasks);
    }

    function setupDateDefaults() {
        const now = getUTCDateWithOffset();
        const today = fmt(now);
        const time = pad(new Date().getHours()) + ":" + pad(now.getMinutes());

        $('input[name="due_date"]').val(today);
        $('input[name="due_time"]').val(time);
    }

    function saveUserIfNeeded() {
        $.post('handler.php?action=save_user', {
            user_id: currentUserId,
            chat_id: currentUserId,
            first_name: tg.initDataUnsafe?.user?.first_name || '',
            last_name: tg.initDataUnsafe?.user?.last_name || '',
            username: tg.initDataUnsafe?.user?.username || ''
        });
    }

    // Calendar
    function renderCalendar() {
        // Получаем текущую дату в UTC+3
        const nowUTC3 = getUTCDateWithOffset();
        const todayStr = fmt(nowUTC3);

        // Создаем дату для отображаемого месяца с учетом UTC+3
        const d = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        // Корректируем на UTC+3
        const dUTC3 = new Date(d.getTime() + (3 * 60 * 60 * 1000));

        $('#monthLabel').text(months[dUTC3.getMonth()] + ' ' + dUTC3.getFullYear());
        const startDay = (dUTC3.getDay() + 6) % 7; // Monday first
        const daysInMonth = new Date(dUTC3.getFullYear(), dUTC3.getMonth() + 1, 0).getDate();
        const grid = $('#calendarGrid');
        grid.empty();

        // Build map date=>count
        const map = {};
        (lastLoadedTasks || []).forEach(t => {
            map[t.due_date] = (map[t.due_date] || 0) + 1;
        });

        for (let i = 0; i < startDay; i++) grid.append('<div class="h-10"></div>');
        for (let day = 1; day <= daysInMonth; day++) {
            // Формируем строку даты с учетом месяца и года из UTC+3 даты
            const dateStr = dUTC3.getFullYear() + "-" + pad(dUTC3.getMonth() + 1) + "-" + pad(day);
            const isToday = dateStr === todayStr;
            const isSelected = dateStr === fmt(selectedDate);
            const has = map[dateStr] > 0;
            const dot = has ?
                '<span class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-emerald-400"></span>' :
                '';
            const cls = 'relative h-10 flex items-center justify-center rounded-full cursor-pointer ' +
                (isSelected ? 'bg-white/20 ' : 'hover:bg-white/10 ') +
                (isToday ? 'ring-2 ring-white/30 ' : '');
            const el = $('<div class="' + cls + '"><span>' + day + '</span>' + dot + '</div>');
            el.on('click', () => {
                // Создаем дату с учетом UTC+3 при клике
                const clickedDate = new Date(dateStr + 'T00:00:00Z');
                // Корректируем на UTC+3
                selectedDate = new Date(clickedDate.getTime() + (3 * 60 * 60 * 1000));
                renderCalendar();
                updateEventCard();
            });
            grid.append(el);
        }
        updateEventCard();
    }

    function updateEventCard() {
        const dateStr = fmt(selectedDate);
        const tasks = (lastLoadedTasks || []).filter(t => t.due_date === dateStr)
            .sort((a, b) => a.due_time.localeCompare(b.due_time));
        if (tasks.length === 0) {
            $('#eventTitle').text('Нет задач');
            $('#eventDate').text(dateStr);
            $('#eventTime').text('');
            return;
        }
        const t = tasks[0];
        $('#eventTitle').text(t.title);
        $('#eventDate').text(t.due_date);
        $('#eventTime').text(t.due_time);
    }
    // Tasks
    function loadTasks() {
        $.get(`handler.php?action=get&user_id=${currentUserId}&filter=all`, function(response) {
            try {
                if (response.tasks !== undefined) {
                    lastLoadedTasks = response.tasks;
                    renderCalendar();
                    renderWeekView(response.tasks);
                    //renderBuckets(response.tasks);
                    //renderStatusBlocks(response.tasks);
                } else if (response.error) {
                    $('#tasksBuckets').html(
                        `<div class='text-center text-hint'>Ошибка: ${response.error}</div>`);
                } else {
                    $('#tasksBuckets').html(`<div class='text-center text-hint'>Неверный ответ сервера</div>`);
                }
            } catch (e) {
                $('#tasksBuckets').html(`<div class='text-center text-hint'>Ошибка обработки</div>`);
            }
        }).fail(function(xhr) {
            $('#tasksBuckets').html(
                `<div class='text-center text-hint'>Ошибка соединения: ${xhr.status}</div>`);
        });
    }

    function renderBuckets(tasks) {
        const todayStr = getTodayUTCPLus3();
        const tomorrowStr = getTomorrowUTCPLus3();

        const buckets = {
            'Сегодня': tasks.filter(t => t.due_date === todayStr),
            'Завтра': tasks.filter(t => t.due_date === tomorrowStr),
            'Предстоящие': tasks.filter(t => t.due_date > tomorrowStr)
        };

        const cont = $('#tasksBuckets');
        cont.empty();

        Object.keys(buckets).forEach(title => {
            const list = buckets[title];
            const section = $('<div></div>');
            section.append(`<div class="text-sm font-semibold mb-2">${title}</div>`);

            if (list.length === 0) {
                section.append('<div class="text-sm text-hint">Нет задач</div>');
                cont.append(section);
                return;
            }

            list.sort((a, b) => a.due_time.localeCompare(b.due_time));
            list.forEach(task => {
                section.append(taskRow(task));
            });
            cont.append(section);
        });
    }

    function renderStatusBlocks(tasks) {
        const pending = tasks.filter(t => t.status !== 'completed');
        const completed = tasks.filter(t => t.status === 'completed');

        // Pending
        const pendCont = $('#tasksPending');
        pendCont.empty();
        if (pending.length === 0) {
            pendCont.append('<div class="text-sm text-hint">Нет задач</div>');
        } else {
            pending.sort((a, b) => (a.due_date + a.due_time).localeCompare(b.due_date + b.due_time));
            pending.forEach(t => pendCont.append(taskRow(t)));
        }

        // Completed
        const compCont = $('#tasksCompleted');
        compCont.empty();
        if (completed.length === 0) {
            compCont.append('<div class="text-sm text-hint">Нет задач</div>');
        } else {
            completed.sort((a, b) => (a.due_date + a.due_time).localeCompare(b.due_date + b.due_time));
            completed.forEach(t => compCont.append(taskRow(t)));
        }
    }

    function priorityLabel(p) {
        if (p === 'high') return 'Высокий';
        if (p === 'low') return 'Низкий';
        return 'Средний';
    }

    function hideTaskMenus() {
        openMenuId = null;
        $('.task-row').removeClass('menu-open');
        $('.task-menu').hide();
    }

    function taskRow(task) {
        const done = task.status === 'completed';
        const prBadge = task.priority === 'high' ? 'bg-red-500' : (task.priority === 'medium' ? 'bg-yellow-500' :
            'bg-emerald-500');
        const id = `menu-${task.id}`;
        const el = $(`
        <div class="task-row group flex items-start gap-3 px-3 py-3 rounded-2xl hover:bg-white/5 ${done?'opacity-70':''}">
          <button class="mt-1 w-5 h-5 rounded-full border border-white/30 flex items-center justify-center">${done?'<i class="fa-solid fa-check text-xs"></i>':''}</button>
          <div class="flex-1">
            <div class="flex items-start justify-between gap-2">
              <div class="font-medium">${escapeHtml(task.title)}</div>
              <div class="relative">
                <button class="task-menu-btn p-1 rounded hover:bg-white/10" data-menu="${id}"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                <div id="${id}" class="task-menu hidden absolute right-0 mt-1 w-44 bgg rounded-xl shadow-soft overflow-hidden text-sm z-10">
                  <a class="block px-3 py-2 hover:bg-white/10 cursor-pointer action-toggle">${done?'Вернуть':'Выполнить'}</a>
                  <a class="block px-3 py-2 hover:bg-white/10 cursor-pointer action-edit">Редактировать</a>
                  <a class="block px-3 py-2 hover:bg-white/10 text-red-400 cursor-pointer action-delete">Удалить</a>
                </div>
              </div>
            </div>
            ${task.description? `<div class="text-sm text-hint mt-1">${escapeHtml(task.description)}</div>`:''}
            <div class="mt-2 flex items-center justify-between text-xs text-hint">
              <div><i class="fa-solid fa-calendar"></i> ${task.due_date} <i class="fa-solid fa-clock ml-2"></i> ${task.due_time}</div>
              <span class="inline-flex items-center gap-2"><span class="w-2 h-2 rounded-full ${prBadge}"></span> ${priorityLabel(task.priority)}</span>
            </div>
          </div>
        </div>`);

        // чекбокс — смена статуса
        el.find('button').first().on('click', function(e) {
            e.stopPropagation();
            toggleTaskStatus(task.id, task.status);
        });

        // кнопка меню — открытие/закрытие
        el.find('.task-menu-btn').on('click', function(e) {
            e.stopPropagation();
            const targetId = $(this).data('menu');
            if (openMenuId === targetId) {
                hideTaskMenus();
                return;
            }
            hideTaskMenus();
            openMenuId = targetId;
            el.addClass('menu-open');
            $('#' + targetId).show();
        });

        // действия меню
        el.find('.action-toggle').on('click', function(e) {
            e.stopPropagation();
            toggleTaskStatus(task.id, task.status);
        });
        el.find('.action-edit').on('click', function(e) {
            e.stopPropagation();
            editTask(task);
        });
        el.find('.action-delete').on('click', function(e) {
            e.stopPropagation();
            deleteTask(task.id);
        });

        return el;
    }

    // Search
    function searchTasks() {
        const q = $('#searchInput').val();
        if (q.length > 2) {
            $.get(`handler.php?action=search&user_id=${currentUserId}&q=${encodeURIComponent(q)}`, function(data) {
                try {
                    const response = typeof data === 'string' ? JSON.parse(data) : data;
                    if (response.tasks) {
                        lastLoadedTasks = response.tasks;
                        renderBuckets(response.tasks);
                        renderStatusBlocks(response.tasks);
                        renderCalendar();
                    }
                } catch (e) {}
            });
        } else if (q.length === 0) {
            loadTasks();
        }
    }
    // CRUD
    function showAddTaskModal() {
        $('#taskForm')[0].reset();
        setupDateDefaults();
        $('#modalTitle').text('Добавить задачу');
        $('#deleteBtn').addClass('hidden');
        openTaskModal();
    }

    function editTask(task) {
        $('#modalTitle').text('Изменить задачу');
        $('#deleteBtn').removeClass('hidden');
        openTaskModal();
        $('input[name="task_id"]').val(task.id);
        $('input[name="title"]').val(task.title);
        $('textarea[name="description"]').val(task.description || '');
        $('input[name="due_date"]').val(task.due_date);
        $('input[name="due_time"]').val(task.due_time);
        $('select[name="priority"]').val(task.priority || 'medium');
        $('select[name="reminder"]').val(task.reminder || 'none');
    }

    function openTaskModal() {
        $('#taskModal').removeClass('hidden').addClass('flex');
    }

    function closeTaskModal() {
        $('#taskModal').addClass('hidden').removeClass('flex');
    }

    function submitTask() {
        const data = {
            user_id: currentUserId,
            title: $('input[name="title"]').val(),
            description: $('textarea[name="description"]').val(),
            due_date: $('input[name="due_date"]').val(),
            due_time: $('input[name="due_time"]').val(),
            priority: $('select[name="priority"]').val(),
            reminder: $('select[name="reminder"]').val()
        };
        const id = $('input[name="task_id"]').val();
        if (id) {
            data.task_id = id;
            $.post('handler.php?action=update', data, function(result) {
                if (result.success) {
                    closeTaskModal();
                    loadTasks();
                } else {
                    alert(result.error || 'Не удалось обновить');
                }
            });
        } else {
            $.post('handler.php?action=add', data, function(result) {
                if (result.success) {
                    closeTaskModal();
                    loadTasks();
                } else {
                    alert(result.error || 'Не удалось добавить');
                }
            });
        }
    }

    function deleteFromModal() {
        const id = $('input[name="task_id"]').val();
        if (!id) return;
        deleteTask(id);
        closeTaskModal();
    }

    function deleteTask(taskId) {
        if (confirm('Удалить задачу?')) {
            $.post('handler.php?action=delete', {
                user_id: currentUserId,
                task_id: taskId
            }, function(result) {
                if (result.success) {
                    loadTasks();
                }
            });
        }
    }

    function toggleTaskStatus(taskId, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        const formData = new FormData();
        formData.append('user_id', currentUserId);
        formData.append('task_id', taskId);
        formData.append('status', newStatus);
        
        // Закрываем меню перед отправкой
        hideTaskMenus();
        
        $.ajax({
            url: 'handler.php?action=update',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(result) {
                if (result && result.success) {
                    loadTasks();
                } else {
                    console.error("Ошибка обновления статуса:", result?.error || 'Неизвестная ошибка');
                    alert('Не удалось обновить статус задачи');
                }
            },
            error: function(xhr, status, error) {
                console.error("Ошибка AJAX:", error);
                alert('Ошибка соединения с сервером');
            }
        });
    }

    // Profile (без кнопки вызова)
    function showProfile() {
        $.get(`handler.php?action=stats&user_id=${currentUserId}`, function(data) {
            try {
                const response = typeof data === 'string' ? JSON.parse(data) : data;
                if (response.stats) {
                    const s = response.stats;
                    const p = s.pending || {
                        count: 0
                    };
                    const c = s.completed || {
                        count: 0
                    };
                    const x = s.cancelled || {
                        count: 0
                    };
                    $('#profileStats').html(`<div class='text-center mb-4'>
                <div class='text-xl font-semibold mb-1'>${(tg.initDataUnsafe?.user?.first_name||'')} ${(tg.initDataUnsafe?.user?.last_name||'')}</div>
                <div class='text-sm text-hint'>@${(tg.initDataUnsafe?.user?.username||'без username')}</div>
              </div>
              <div class='grid grid-cols-3 gap-2 text-center'>
                <div class='rounded-2xl p-3 bg-emerald-500/20'><div class='text-2xl font-semibold'>${p.count}</div><div class='text-xs'>В работе</div></div>
                <div class='rounded-2xl p-3 bg-blue-500/20'><div class='text-2xl font-semibold'>${c.count}</div><div class='text-xs'>Выполнено</div></div>
                <div class='rounded-2xl p-3 bg-white/10'><div class='text-2xl font-semibold'>${x.count}</div><div class='text-xs'>Отменено</div></div>
              </div>`);
                    openProfile();
                }
            } catch (e) {}
        });
    }

    function openProfile() {
        $('#profileModal').removeClass('hidden').addClass('flex');
    }

    function closeProfile() {
        $('#profileModal').addClass('hidden').removeClass('flex');
    }
    </script>
</body>

</html>