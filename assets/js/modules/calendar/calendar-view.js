import { store } from '../../core/store.js';
import { addMonths, buildCalendarMatrix, formatMonthTitle, weekdayLabels } from '../../utils/dates.js';

const colorPriorityClass = {
  1: 'dot-low',
  2: 'dot-medium',
  3: 'dot-high',
};

export function initCalendarView({ onSelectDate, onOpenPicker, onToday, onPrevMonth, onNextMonth, onProfile }) {
  const weekdayRow = document.getElementById('weekdayRow');
  const grid = document.getElementById('calendarGrid');
  const monthTitle = document.getElementById('monthTitleText');

  document.getElementById('monthPickerButton').addEventListener('click', onOpenPicker);
  document.getElementById('todayButton').addEventListener('click', onToday);
  document.getElementById('prevMonthButton').addEventListener('click', onPrevMonth);
  document.getElementById('nextMonthButton').addEventListener('click', onNextMonth);
  document.getElementById('profileButton').addEventListener('click', onProfile);

  store.subscribe((state) => {
    if (!state.currentYear || !state.currentMonth) return;

    monthTitle.textContent = formatMonthTitle(state.currentYear, state.currentMonth);
    weekdayRow.innerHTML = weekdayLabels(state.settings.week_start)
      .map((label) => `<span>${label}</span>`)
      .join('');

    const cells = buildCalendarMatrix(state.currentYear, state.currentMonth, state.settings.week_start);
    grid.innerHTML = cells.map((cell) => {
      const summary = state.monthSummary[cell.date];
      const isSelected = cell.date === state.selectedDate;
      const dots = summary ? buildSummaryDots(summary) : '';
      return `
        <button class="calendar-cell ${cell.inMonth ? '' : 'is-outside'} ${cell.isToday ? 'is-today' : ''} ${isSelected ? 'is-selected' : ''}" data-date="${cell.date}">
          <span class="calendar-day-number">${cell.day}</span>
          <span class="calendar-dots">${dots}</span>
        </button>
      `;
    }).join('');

    grid.querySelectorAll('[data-date]').forEach((button) => {
      button.addEventListener('click', () => onSelectDate(button.dataset.date));
    });

    renderAvatarButton(state.profile);
  });
}

function buildSummaryDots(summary) {
  const maxDots = Math.min(summary.total, 3);
  const dots = [];
  for (let i = 0; i < maxDots; i += 1) {
    dots.push(`<i class="calendar-dot ${colorPriorityClass[summary.priority_level] || 'dot-medium'}"></i>`);
  }
  if (summary.total > 3) {
    dots.push(`<i class="calendar-dot more">+${summary.total - 3}</i>`);
  }
  return dots.join('');
}

function renderAvatarButton(profile) {
  const target = document.getElementById('profileButton');
  if (!target || !profile) return;
  const initial = (profile.display_name || profile.first_name || 'U').trim().charAt(0).toUpperCase();
  target.innerHTML = `<span>${initial}</span>`;
}

export function shiftVisibleMonth(delta) {
  const next = addMonths(store.state.currentYear, store.state.currentMonth, delta);
  store.set({
    currentYear: next.year,
    currentMonth: next.month,
    ui: { pickerYear: next.year },
  });
}
