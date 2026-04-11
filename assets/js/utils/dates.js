export const RU_MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
export const RU_MONTHS_GEN = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
export const RU_WEEKDAYS_SHORT_MONDAY = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
export const RU_WEEKDAYS_SHORT_SUNDAY = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
export const RU_WEEKDAYS_FULL = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];

export function isoDate(date) {
  const d = typeof date === 'string' ? new Date(date + 'T00:00:00') : new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

export function formatMonthTitle(year, month) {
  return `${RU_MONTHS[month - 1]} ${year}`;
}

export function formatDateHuman(dateString) {
  const date = new Date(dateString + 'T00:00:00');
  return `${date.getDate()} ${RU_MONTHS_GEN[date.getMonth()]} ${date.getFullYear()}`;
}

export function formatAgendaTitle(dateString) {
  const date = new Date(dateString + 'T00:00:00');
  const weekday = RU_WEEKDAYS_FULL[date.getDay()];
  return `${weekday}, ${date.getDate()} ${RU_MONTHS_GEN[date.getMonth()]}`;
}

export function buildCalendarMatrix(year, month, weekStart = 1) {
  const firstOfMonth = new Date(year, month - 1, 1);
  const lastOfMonth = new Date(year, month, 0);
  const offset = weekStart === 1
    ? (firstOfMonth.getDay() + 6) % 7
    : firstOfMonth.getDay();

  const startDate = new Date(year, month - 1, 1 - offset);
  const totalCells = Math.ceil((offset + lastOfMonth.getDate()) / 7) * 7;
  const cells = [];

  for (let i = 0; i < totalCells; i += 1) {
    const current = new Date(startDate);
    current.setDate(startDate.getDate() + i);
    cells.push({
      date: isoDate(current),
      day: current.getDate(),
      inMonth: current.getMonth() === month - 1,
      isToday: isoDate(new Date()) === isoDate(current),
    });
  }

  return cells;
}

export function addMonths(year, month, delta) {
  const base = new Date(year, month - 1 + delta, 1);
  return {
    year: base.getFullYear(),
    month: base.getMonth() + 1,
  };
}

export function weekdayLabels(weekStart = 1) {
  return weekStart === 1 ? RU_WEEKDAYS_SHORT_MONDAY : RU_WEEKDAYS_SHORT_SUNDAY;
}

export function clampMonth(month) {
  return Math.max(1, Math.min(12, month));
}
