import { store } from './store.js';

const accentMap = {
  blue: ['#0A84FF', 'rgba(10,132,255,.16)'],
  purple: ['#8B5CF6', 'rgba(139,92,246,.16)'],
  green: ['#22C55E', 'rgba(34,197,94,.16)'],
  pink: ['#EC4899', 'rgba(236,72,153,.16)'],
  orange: ['#F97316', 'rgba(249,115,22,.16)'],
  red: ['#EF4444', 'rgba(239,68,68,.16)'],
  teal: ['#14B8A6', 'rgba(20,184,166,.16)'],
};

export function applyTheme(settings) {
  const themeMode = resolveMode(settings.theme_mode);
  const [accent, accentSoft] = accentMap[settings.accent_color] || accentMap.blue;

  document.documentElement.dataset.theme = themeMode;
  document.documentElement.style.setProperty('--accent', accent);
  document.documentElement.style.setProperty('--accent-soft', accentSoft);
  document.documentElement.style.setProperty('--accent-strong', accent);
}

function resolveMode(mode) {
  if (mode === 'system') {
    const tgTheme = window.Telegram?.WebApp?.colorScheme;
    if (tgTheme === 'dark' || tgTheme === 'light') return tgTheme;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  return mode;
}

store.subscribe((state) => applyTheme(state.settings));
