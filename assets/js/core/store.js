const listeners = new Set();

export const store = {
  state: {
    token: localStorage.getItem('tg_calendar_token') || '',
    profile: null,
    settings: {
      theme_mode: 'system',
      accent_color: 'blue',
      week_start: 1,
    },
    selectedDate: null,
    currentYear: null,
    currentMonth: null,
    monthSummary: {},
    tasks: [],
    ui: {
      activeSheet: null,
      pickerYear: new Date().getFullYear(),
      taskFormMode: 'create',
      editingTaskId: null,
    },
  },
  subscribe(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
  set(partial) {
    this.state = mergeDeep(this.state, partial);
    listeners.forEach((fn) => fn(this.state));
  },
  replace(nextState) {
    this.state = nextState;
    listeners.forEach((fn) => fn(this.state));
  },
};

function mergeDeep(target, source) {
  if (!source || typeof source !== 'object') return target;
  const output = Array.isArray(target) ? [...target] : { ...target };
  Object.keys(source).forEach((key) => {
    if (Array.isArray(source[key])) {
      output[key] = [...source[key]];
    } else if (source[key] && typeof source[key] === 'object') {
      output[key] = mergeDeep(target[key] || {}, source[key]);
    } else {
      output[key] = source[key];
    }
  });
  return output;
}
