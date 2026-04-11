import { store } from '../../core/store.js';

const ACCENTS = ['blue', 'purple', 'green', 'pink', 'orange', 'red', 'teal'];

export function initProfileView({ onOpen, onClose, onSave, onUploadAvatar, onRemoveAvatar, onSaveTheme }) {
  const sheet = document.getElementById('profileSheet');
  sheet.querySelectorAll('[data-close-sheet]').forEach((button) => button.addEventListener('click', onClose));
  document.querySelector('[data-action="profile"]')?.addEventListener('click', onOpen);
  document.getElementById('saveProfileButton').addEventListener('click', onSave);
  document.getElementById('avatarUploadInput').addEventListener('change', (event) => {
    const file = event.target.files?.[0];
    if (file) onUploadAvatar(file);
    event.target.value = '';
  });
  document.getElementById('removeAvatarButton').addEventListener('click', onRemoveAvatar);

  renderSegmented('themeModeControl', [
    { value: 'light', label: 'Светлая' },
    { value: 'dark', label: 'Тёмная' },
    { value: 'system', label: 'Система' },
  ], onSaveTheme, 'theme');

  renderSegmented('weekStartControl', [
    { value: '1', label: 'Пн' },
    { value: '0', label: 'Вс' },
  ], onSaveTheme, 'week');

  const accentPalette = document.getElementById('accentPalette');
  accentPalette.innerHTML = ACCENTS.map((color) => `<button class="color-option" data-accent="${color}"><span class="swatch ${color}"></span></button>`).join('');
  accentPalette.querySelectorAll('[data-accent]').forEach((button) => button.addEventListener('click', () => {
    accentPalette.dataset.selectedAccent = button.dataset.accent;
    syncPaletteSelection();
    onSaveTheme();
  }));

  store.subscribe((state) => {
    const { profile, settings } = state;
    if (!profile) return;

    document.getElementById('profileDisplayNameInput').value = profile.display_name || '';
    document.getElementById('profileTelegramLine').textContent = profile.telegram_username ? `@${profile.telegram_username}` : 'username недоступен';
    renderAvatar(profile);
    document.getElementById('themeModeControl').dataset.selectedValue = settings.theme_mode;
    document.getElementById('weekStartControl').dataset.selectedValue = String(settings.week_start);
    document.getElementById('accentPalette').dataset.selectedAccent = settings.accent_color;
    syncPaletteSelection();
    syncSegmentedSelection('themeModeControl');
    syncSegmentedSelection('weekStartControl');
  });
}

function renderSegmented(targetId, items, onChange, namespace) {
  const container = document.getElementById(targetId);
  container.innerHTML = items.map((item) => `<button data-${namespace}="${item.value}">${item.label}</button>`).join('');
  container.querySelectorAll('button').forEach((button) => button.addEventListener('click', () => {
    container.dataset.selectedValue = namespace === 'theme' ? button.dataset.theme : button.dataset.week;
    syncSegmentedSelection(targetId);
    onChange();
  }));
}

function syncPaletteSelection() {
  const accentPalette = document.getElementById('accentPalette');
  accentPalette.querySelectorAll('[data-accent]').forEach((button) => {
    button.classList.toggle('selected', button.dataset.accent === accentPalette.dataset.selectedAccent);
  });
}

function syncSegmentedSelection(targetId) {
  const container = document.getElementById(targetId);
  container.querySelectorAll('button').forEach((button) => {
    const value = button.dataset.theme || button.dataset.week;
    button.classList.toggle('selected', value === container.dataset.selectedValue);
  });
}

function renderAvatar(profile) {
  const target = document.getElementById('profileAvatarLarge');
  if (profile.avatar_url) {
    target.innerHTML = `<img src="${profile.avatar_url}" alt="avatar">`;
  } else {
    const initial = (profile.display_name || profile.first_name || 'U').trim().charAt(0).toUpperCase();
    target.innerHTML = `<span>${initial}</span>`;
  }
}

export function getProfilePayload() {
  return {
    display_name: document.getElementById('profileDisplayNameInput').value.trim(),
    theme_mode: document.getElementById('themeModeControl').dataset.selectedValue || 'system',
    accent_color: document.getElementById('accentPalette').dataset.selectedAccent || 'blue',
    week_start: Number(document.getElementById('weekStartControl').dataset.selectedValue || 1),
  };
}
