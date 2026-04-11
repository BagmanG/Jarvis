import { store } from '../../core/store.js';
import { formatAgendaTitle } from '../../utils/dates.js';

const colorNames = {
  blue: 'var(--task-blue)',
  purple: 'var(--task-purple)',
  green: 'var(--task-green)',
  pink: 'var(--task-pink)',
  orange: 'var(--task-orange)',
  red: 'var(--task-red)',
  teal: 'var(--task-teal)',
};

export function initTaskView({ onCreate, onToggleComplete, onEdit }) {
  document.getElementById('addTaskButton').addEventListener('click', onCreate);
  document.getElementById('bottomAddButton').addEventListener('click', onCreate);
  document.getElementById('emptyCreateButton').addEventListener('click', onCreate);

  store.subscribe((state) => {
    if (!state.selectedDate) return;
    const tasksList = document.getElementById('tasksList');
    const emptyState = document.getElementById('emptyState');
    const title = document.getElementById('selectedDateTitle');
    const meta = document.getElementById('selectedDateMeta');

    title.textContent = formatAgendaTitle(state.selectedDate);
    meta.textContent = `${state.tasks.length} ${pluralizeTasks(state.tasks.length)}`;

    if (!state.tasks.length) {
      tasksList.innerHTML = '';
      emptyState.classList.remove('hidden');
      return;
    }

    emptyState.classList.add('hidden');
    tasksList.innerHTML = state.tasks.map((task) => `
      <article class="task-card ${task.status === 'completed' ? 'is-completed' : ''}" data-task-id="${task.id}">
        <button class="task-check ${task.status === 'completed' ? 'checked' : ''}" data-action="toggle" aria-label="Переключить выполнение"></button>
        <div class="task-color" style="background:${colorNames[task.color] || 'var(--accent)'}"></div>
        <div class="task-content" data-action="edit">
          <div class="task-top-line">
            <h3>${escapeHtml(task.title)}</h3>
            ${task.priority === 'high' ? '<span class="task-badge high">Важно</span>' : ''}
            ${task.priority === 'low' ? '<span class="task-badge">Низкий</span>' : ''}
          </div>
          <div class="task-meta-line">
            <span>${task.all_day ? 'Весь день' : formatTimeRange(task)}</span>
            <span>${statusLabel(task.status)}</span>
          </div>
          ${task.description ? `<p class="task-description">${escapeHtml(task.description)}</p>` : ''}
        </div>
      </article>
    `).join('');

    tasksList.querySelectorAll('.task-card').forEach((card) => {
      card.querySelector('[data-action="toggle"]').addEventListener('click', (event) => {
        event.stopPropagation();
        onToggleComplete(Number(card.dataset.taskId));
      });
      card.querySelector('[data-action="edit"]').addEventListener('click', () => onEdit(Number(card.dataset.taskId)));
    });
  });
}

export function setTaskLoading(isLoading) {
  const skeleton = document.getElementById('taskSkeleton');
  if (!isLoading) {
    skeleton.classList.add('hidden');
    skeleton.innerHTML = '';
    return;
  }
  skeleton.classList.remove('hidden');
  skeleton.innerHTML = new Array(3).fill(0).map(() => '<div class="task-skeleton"></div>').join('');
}

function formatTimeRange(task) {
  if (!task.time_start && !task.time_end) return 'Без времени';
  if (task.time_start && task.time_end) return `${task.time_start} — ${task.time_end}`;
  return task.time_start || task.time_end;
}

function statusLabel(status) {
  if (status === 'completed') return 'Выполнена';
  if (status === 'archived') return 'Архив';
  return 'Активна';
}

function pluralizeTasks(count) {
  const mod10 = count % 10;
  const mod100 = count % 100;
  if (mod10 === 1 && mod100 !== 11) return 'задача';
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return 'задачи';
  return 'задач';
}

function escapeHtml(text = '') {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
