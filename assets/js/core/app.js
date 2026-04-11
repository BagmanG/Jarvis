import { api } from './api.js';
import { store } from './store.js';
import './theme.js';
import { initCalendarView, shiftVisibleMonth } from '../modules/calendar/calendar-view.js';
import { initTaskForm, openTaskForm, closeTaskForm, getTaskFormPayload } from '../modules/tasks/task-form.js';
import { initTaskView, setTaskLoading } from '../modules/tasks/task-view.js';
import { getProfilePayload, initProfileView } from '../modules/profile/profile-view.js';
import { formatMonthTitle, isoDate, RU_MONTHS } from '../utils/dates.js';

const tg = window.Telegram?.WebApp;
const monthsShort = RU_MONTHS.map((month, index) => ({ label: month.slice(0, 3), value: index + 1 }));

setupTelegram();
setupBackdrop();
initCalendarView({
  onSelectDate: handleSelectDate,
  onOpenPicker: openMonthPicker,
  onToday: jumpToday,
  onPrevMonth: () => changeVisibleMonth(-1),
  onNextMonth: () => changeVisibleMonth(1),
  onProfile: openProfile,
});
initTaskView({
  onCreate: () => openTaskForm(),
  onToggleComplete: toggleTaskComplete,
  onEdit: editTask,
});
initTaskForm({
  onSave: saveTask,
  onDelete: deleteCurrentTask,
  onDuplicate: duplicateCurrentTask,
  onMarkCompleted: completeCurrentTask,
  onClose: closeSheet,
});
initProfileView({
  onOpen: openProfile,
  onClose: closeSheet,
  onSave: saveProfile,
  onUploadAvatar: uploadAvatar,
  onRemoveAvatar: removeAvatar,
  onSaveTheme: saveTheme,
});
wireMonthPicker();
wireRetry();
boot();

async function boot() {
  const start = performance.now();
  try {
    const selectedDate = isoDate(new Date());
    const authPayload = await api.authTelegram(buildAuthPayload(selectedDate));
    store.set({
      token: authPayload.token,
      profile: authPayload.profile,
      settings: authPayload.settings,
      selectedDate: authPayload.selected_date,
      currentYear: authPayload.month.year,
      currentMonth: authPayload.month.month,
      monthSummary: authPayload.month.summary || {},
      tasks: authPayload.tasks || [],
      ui: { pickerYear: authPayload.month.year },
    });
    localStorage.setItem('tg_calendar_token', authPayload.token);

    const elapsed = performance.now() - start;
    const remaining = Math.max(0, 850 - elapsed);
    setTimeout(() => {
      document.getElementById('loaderScreen').classList.add('hidden');
      document.getElementById('mainView').classList.remove('hidden');
      //showToast('Календарь готов ✨');
    }, remaining); 
  } catch (error) {
    handleBootError(error);
  }
}

function buildAuthPayload(selectedDate) {
  const unsafeUser = tg?.initDataUnsafe?.user || {
    id: 999001,
    first_name: 'Demo',
    last_name: 'Mode',
    username: 'demo_calendar_user',
  };
  return {
    initData: tg?.initData || '',
    selected_date: selectedDate,
    user: unsafeUser,
  };
}

function setupTelegram() {
  if (!tg) return;
  tg.ready();
  tg.expand();
  tg.enableClosingConfirmation?.();
}

function setupBackdrop() {
  document.getElementById('sheetBackdrop').addEventListener('click', closeSheet);
  store.subscribe((state) => {
    const activeSheet = state.ui.activeSheet;
    const backdrop = document.getElementById('sheetBackdrop');
    backdrop.classList.toggle('hidden', !activeSheet);
    ['taskSheet', 'profileSheet', 'monthPickerSheet'].forEach((id) => {
      const element = document.getElementById(id);
      const open = activeSheet && id.toLowerCase().includes(activeSheet);
      element.classList.toggle('hidden', !open);
      element.classList.toggle('is-open', open);
    });
    renderMonthPicker();
  });
}

function wireRetry() {
  document.getElementById('retryBootBtn').addEventListener('click', () => {
    document.getElementById('retryBootBtn').classList.add('hidden');
    document.querySelector('#loaderScreen p').textContent = 'Секунду, собираем календарь, профиль и ваши настройки.';
    boot();
  });
}

function wireMonthPicker() {
  document.getElementById('pickerPrevYear').addEventListener('click', () => store.set({ ui: { pickerYear: store.state.ui.pickerYear - 1 } }));
  document.getElementById('pickerNextYear').addEventListener('click', () => store.set({ ui: { pickerYear: store.state.ui.pickerYear + 1 } }));
  document.getElementById('jumpTodayButton').addEventListener('click', () => {
    jumpToday();
    closeSheet();
  });
}

function renderMonthPicker() {
  const year = store.state.ui.pickerYear;
  document.getElementById('pickerYearLabel').textContent = year;
  const grid = document.getElementById('monthPickerGrid');
  grid.innerHTML = monthsShort.map((item) => `
    <button class="month-pick ${item.value === store.state.currentMonth && year === store.state.currentYear ? 'selected' : ''}" data-month="${item.value}">
      ${item.label}
    </button>
  `).join('');
  grid.querySelectorAll('[data-month]').forEach((button) => button.addEventListener('click', async () => {
    store.set({ currentYear: year, currentMonth: Number(button.dataset.month) });
    await loadMonth(store.state.currentYear, store.state.currentMonth);
    closeSheet();
  }));
}

function openMonthPicker() {
  store.set({ ui: { activeSheet: 'monthpicker', pickerYear: store.state.currentYear } });
}

function openProfile() {
  store.set({ ui: { activeSheet: 'profile' } });
}

function closeSheet() {
  closeTaskForm();
  store.set({ ui: { activeSheet: null } });
}

async function handleSelectDate(date) {
  store.set({ selectedDate: date });
  if (!isCurrentVisibleMonth(date)) {
    const d = new Date(date + 'T00:00:00');
    store.set({ currentYear: d.getFullYear(), currentMonth: d.getMonth() + 1 });
    await loadMonth(d.getFullYear(), d.getMonth() + 1);
  }
  await loadTasks(date);
}

async function jumpToday() {
  const today = isoDate(new Date());
  const now = new Date();
  store.set({
    selectedDate: today,
    currentYear: now.getFullYear(),
    currentMonth: now.getMonth() + 1,
    ui: { pickerYear: now.getFullYear() },
  });
  await Promise.all([
    loadMonth(now.getFullYear(), now.getMonth() + 1),
    loadTasks(today),
  ]);
}

async function changeVisibleMonth(delta) {
  shiftVisibleMonth(delta);
  await loadMonth(store.state.currentYear, store.state.currentMonth);
}

async function loadMonth(year, month) {
  try {
    const data = await api.getMonth(year, month);
    store.set({ monthSummary: data.summary || {} });
    document.getElementById('monthTitleText').textContent = formatMonthTitle(year, month);
  } catch (error) {
    showToast(error.message || 'Не удалось загрузить месяц', 'error');
  }
}

async function loadTasks(date) {
  setTaskLoading(true);
  try {
    const data = await api.getTasks(date);
    store.set({ tasks: data.items || [] });
  } catch (error) {
    showToast(error.message || 'Не удалось загрузить задачи', 'error');
  } finally {
    setTaskLoading(false);
  }
}

async function saveTask() {
  const payload = getTaskFormPayload();
  try {
    let task;
    if (store.state.ui.taskFormMode === 'edit' && store.state.ui.editingTaskId) {
      task = await api.updateTask(store.state.ui.editingTaskId, payload);
      showToast('Задача сохранена');
    } else {
      task = await api.createTask(payload);
      showToast('Задача создана');
    }

    closeSheet();
    await afterTaskMutation(task.date);
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function editTask(taskId) {
  const task = store.state.tasks.find((item) => item.id === taskId);
  if (task) {
    openTaskForm(task);
  }
}

async function toggleTaskComplete(taskId) {
  const task = store.state.tasks.find((item) => item.id === taskId);
  if (!task) return;
  const nextStatus = task.status === 'completed' ? 'active' : 'completed';
  try {
    await api.updateTaskStatus(taskId, nextStatus);
    await afterTaskMutation(store.state.selectedDate);
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function completeCurrentTask() {
  if (!store.state.ui.editingTaskId) return;
  try {
    await api.updateTaskStatus(store.state.ui.editingTaskId, 'completed');
    closeSheet();
    await afterTaskMutation(store.state.selectedDate);
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function deleteCurrentTask() {
  if (!store.state.ui.editingTaskId) return;
  if (!confirm('Удалить задачу?')) return;
  try {
    await api.deleteTask(store.state.ui.editingTaskId);
    closeSheet();
    showToast('Задача удалена');
    await afterTaskMutation(store.state.selectedDate);
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function duplicateCurrentTask() {
  const task = store.state.tasks.find((item) => item.id === store.state.ui.editingTaskId);
  if (!task) return;
  const payload = {
    title: `${task.title} (копия)`,
    description: task.description,
    date: task.date,
    time_start: task.time_start || '',
    time_end: task.time_end || '',
    all_day: task.all_day,
    priority: task.priority,
    status: 'active',
    color: task.color,
  };
  try {
    await api.createTask(payload);
    showToast('Копия создана');
    await afterTaskMutation(task.date);
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function afterTaskMutation(date) {
  const d = new Date(date + 'T00:00:00');
  await Promise.all([
    loadMonth(d.getFullYear(), d.getMonth() + 1),
    loadTasks(store.state.selectedDate),
  ]);
}

async function saveProfile() {
  const payload = getProfilePayload();
  try {
    const profile = await api.updateProfile(payload.display_name);
    store.set({ profile });
    await saveTheme(true);
    showToast('Профиль обновлён');
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function saveTheme(silent = false) {
  const payload = getProfilePayload();
  try {
    const settings = await api.updateTheme({
      theme_mode: payload.theme_mode,
      accent_color: payload.accent_color,
      week_start: payload.week_start,
    });
    store.set({ settings });
    if (!silent) showToast('Тема обновлена');
  } catch (error) {
    if (!silent) showToast(readError(error), 'error');
  }
}

async function uploadAvatar(file) {
  try {
    const profile = await api.uploadAvatar(file);
    store.set({ profile });
    showToast('Аватар загружен');
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

async function removeAvatar() {
  try {
    const profile = await api.deleteAvatar();
    store.set({ profile });
    showToast('Аватар удалён');
  } catch (error) {
    showToast(readError(error), 'error');
  }
}

function isCurrentVisibleMonth(dateString) {
  const d = new Date(dateString + 'T00:00:00');
  return d.getFullYear() === store.state.currentYear && d.getMonth() + 1 === store.state.currentMonth;
}

function handleBootError(error) {
  document.querySelector('#loaderScreen h1').textContent = 'Не удалось загрузить приложение';
  document.querySelector('#loaderScreen p').textContent = readError(error) || 'Проблема соединения или авторизации Telegram. Нажмите «Повторить». '; 
  document.getElementById('retryBootBtn').classList.remove('hidden');
}

function showToast(message, tone = 'success') {
  const stack = document.getElementById('toastStack');
  const item = document.createElement('div');
  item.className = `toast ${tone}`;
  item.textContent = message;
  stack.appendChild(item);
  setTimeout(() => item.classList.add('is-visible'), 20);
  setTimeout(() => {
    item.classList.remove('is-visible');
    setTimeout(() => item.remove(), 240);
  }, 2800);
}

function readError(error) {
  return error?.payload?.error?.message || error?.message || 'Что-то пошло не так';
}
