import { store } from '../../core/store.js';

const COLORS = ['blue', 'purple', 'green', 'pink', 'orange', 'red', 'teal'];

export function initTaskForm({ onSave, onDelete, onDuplicate, onMarkCompleted, onClose }) {
  const sheet = document.getElementById('taskSheet');
  const saveButton = document.getElementById('saveTaskButton');
  const closeButtons = sheet.querySelectorAll('[data-close-sheet]');

  const palette = document.getElementById('taskColorPalette');
  palette.innerHTML = COLORS.map((color) => `<button class="color-option" data-color="${color}"><span class="swatch ${color}"></span></button>`).join('');

  closeButtons.forEach((button) => button.addEventListener('click', onClose));
  saveButton.addEventListener('click', onSave);
  document.getElementById('deleteTaskButton').addEventListener('click', onDelete);
  document.getElementById('duplicateTaskButton').addEventListener('click', onDuplicate);
  document.getElementById('completeTaskButton').addEventListener('click', onMarkCompleted);
  document.getElementById('taskAllDayInput').addEventListener('change', toggleTimeRows);
  palette.querySelectorAll('[data-color]').forEach((button) => button.addEventListener('click', () => selectColor(button.dataset.color)));

  store.subscribe(() => {
    const mode = store.state.ui.taskFormMode;
    const task = getCurrentTask();
    document.getElementById('taskSheetTitle').textContent = mode === 'edit' ? 'Редактирование' : 'Новая задача';
    document.getElementById('deleteTaskButton').classList.toggle('hidden', mode !== 'edit');
    document.getElementById('duplicateTaskButton').classList.toggle('hidden', mode !== 'edit');
    document.getElementById('completeTaskButton').classList.toggle('hidden', mode !== 'edit');
    if (mode === 'edit' && task) fillTaskForm(task);
  });
}

export function openTaskForm(task = null) {
  store.set({
    ui: {
      activeSheet: 'task',
      taskFormMode: task ? 'edit' : 'create',
      editingTaskId: task ? task.id : null,
    },
  });
  if (task) fillTaskForm(task);
  else resetTaskForm(store.state.selectedDate);
}

export function closeTaskForm() {
  store.set({ ui: { activeSheet: null, taskFormMode: 'create', editingTaskId: null } });
}

export function getTaskFormPayload() {
  return {
    title: document.getElementById('taskTitleInput').value.trim(),
    description: document.getElementById('taskDescriptionInput').value.trim(),
    date: document.getElementById('taskDateInput').value,
    time_start: document.getElementById('taskTimeStartInput').value,
    time_end: document.getElementById('taskTimeEndInput').value,
    all_day: document.getElementById('taskAllDayInput').checked,
    priority: document.getElementById('taskPriorityInput').value,
    status: document.getElementById('taskStatusInput').value,
    color: document.getElementById('taskColorPalette').dataset.selectedColor || 'blue',
  };
}

function fillTaskForm(task) {
  document.getElementById('taskTitleInput').value = task.title || '';
  document.getElementById('taskDescriptionInput').value = task.description || '';
  document.getElementById('taskDateInput').value = task.date || store.state.selectedDate;
  document.getElementById('taskTimeStartInput').value = task.time_start || '';
  document.getElementById('taskTimeEndInput').value = task.time_end || '';
  document.getElementById('taskAllDayInput').checked = !!task.all_day;
  document.getElementById('taskPriorityInput').value = task.priority || 'medium';
  document.getElementById('taskStatusInput').value = task.status || 'active';
  selectColor(task.color || 'blue');
  toggleTimeRows();
}

function resetTaskForm(date) {
  document.getElementById('taskTitleInput').value = '';
  document.getElementById('taskDescriptionInput').value = '';
  document.getElementById('taskDateInput').value = date || '';
  document.getElementById('taskTimeStartInput').value = '';
  document.getElementById('taskTimeEndInput').value = '';
  document.getElementById('taskAllDayInput').checked = false;
  document.getElementById('taskPriorityInput').value = 'medium';
  document.getElementById('taskStatusInput').value = 'active';
  selectColor('blue');
  toggleTimeRows();
}

function toggleTimeRows() {
  const hidden = document.getElementById('taskAllDayInput').checked;
  document.querySelectorAll('.task-time-row').forEach((row) => row.classList.toggle('hidden', hidden));
  if (hidden) {
    document.getElementById('taskTimeStartInput').value = '';
    document.getElementById('taskTimeEndInput').value = '';
  }
}

function selectColor(color) {
  const palette = document.getElementById('taskColorPalette');
  palette.dataset.selectedColor = color;
  palette.querySelectorAll('[data-color]').forEach((button) => {
    button.classList.toggle('selected', button.dataset.color === color);
  });
}

function getCurrentTask() {
  const taskId = store.state.ui.editingTaskId;
  return store.state.tasks.find((item) => item.id === taskId) || null;
}
