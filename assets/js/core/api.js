import { store } from './store.js';

const API_BASE = window.APP_CONFIG.apiBase.replace(/\/$/, '');

async function request(path, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.headers || {}),
  };

  const token = store.state.token;
  if (token && !headers.Authorization) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE}${path.startsWith('/') ? path : `/${path}`}`, {
    ...options,
    headers,
  });

  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json') ? await response.json() : null;

  if (!response.ok || !payload?.success) {
    const error = new Error(payload?.error?.message || 'Ошибка запроса');
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  return payload.data;
}

export const api = {
  authTelegram(data) {
    return request('/auth/telegram', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
  },
  getBootstrap(selectedDate) {
    return request(`/bootstrap?selected_date=${encodeURIComponent(selectedDate)}`);
  },
  getMonth(year, month) {
    return request(`/calendar/month?year=${year}&month=${month}`);
  },
  getTasks(date) {
    return request(`/tasks?date=${encodeURIComponent(date)}`);
  },
  createTask(payload) {
    return request('/tasks', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
  },
  updateTask(taskId, payload) {
    return request(`/tasks/${taskId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
  },
  updateTaskStatus(taskId, status) {
    return request(`/tasks/${taskId}/status`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status }),
    });
  },
  deleteTask(taskId) {
    return request(`/tasks/${taskId}`, {
      method: 'DELETE',
    });
  },
  updateProfile(displayName) {
    return request('/profile', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ display_name: displayName }),
    });
  },

  updateTheme(payload) {
    return request('/settings/theme', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
  },
};
