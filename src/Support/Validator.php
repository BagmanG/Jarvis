<?php
namespace MiniApp\Support;

class Validator
{
    public static function validateTask(array $data, bool $partial = false): array
    {
        $errors = [];
        $clean = [];

        $title = trim((string) ($data['title'] ?? ''));
        if (!$partial || array_key_exists('title', $data)) {
            if ($title === '') {
                $errors['title'] = 'Название задачи обязательно.';
            } elseif (mb_strlen($title) > 150) {
                $errors['title'] = 'Название не должно превышать 150 символов.';
            } else {
                $clean['title'] = $title;
            }
        }

        if (!$partial || array_key_exists('description', $data)) {
            $description = trim((string) ($data['description'] ?? ''));
            if (mb_strlen($description) > 2000) {
                $errors['description'] = 'Описание не должно превышать 2000 символов.';
            } else {
                $clean['description'] = $description ?: null;
            }
        }

        if (!$partial || array_key_exists('date', $data) || array_key_exists('task_date', $data)) {
            $date = (string) ($data['date'] ?? $data['task_date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $errors['date'] = 'Дата должна быть в формате YYYY-MM-DD.';
            } else {
                $clean['task_date'] = $date;
            }
        }

        if (!$partial || array_key_exists('all_day', $data)) {
            $clean['all_day'] = !empty($data['all_day']) ? 1 : 0;
        }

        foreach (['time_start', 'time_end'] as $field) {
            if (!$partial || array_key_exists($field, $data)) {
                $value = trim((string) ($data[$field] ?? ''));
                if ($value === '') {
                    $clean[$field] = null;
                } elseif (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                    $errors[$field] = 'Время должно быть в формате HH:MM.';
                } else {
                    $clean[$field] = $value . ':00';
                }
            }
        }

        $allDay = $clean['all_day'] ?? (!empty($data['all_day']) ? 1 : 0);
        if ($allDay) {
            $clean['time_start'] = null;
            $clean['time_end'] = null;
        } else {
            $timeStart = $clean['time_start'] ?? null;
            $timeEnd = $clean['time_end'] ?? null;
            if ($timeStart && $timeEnd && strcmp($timeEnd, $timeStart) < 0) {
                $errors['time_end'] = 'Время окончания не может быть раньше времени начала.';
            }
        }

        if (!$partial || array_key_exists('priority', $data)) {
            $priority = (string) ($data['priority'] ?? 'medium');
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                $errors['priority'] = 'Недопустимый приоритет.';
            } else {
                $clean['priority'] = $priority;
            }
        }

        if (!$partial || array_key_exists('status', $data)) {
            $status = (string) ($data['status'] ?? 'active');
            if (!in_array($status, ['active', 'completed', 'archived'], true)) {
                $errors['status'] = 'Недопустимый статус.';
            } else {
                $clean['status'] = $status;
            }
        }

        if (!$partial || array_key_exists('color', $data)) {
            $color = (string) ($data['color'] ?? 'blue');
            $allowed = ['blue', 'purple', 'green', 'pink', 'orange', 'red', 'teal'];
            $clean['color'] = in_array($color, $allowed, true) ? $color : 'blue';
        }

        if (!$partial || array_key_exists('reminder_minutes', $data)) {
            $reminderMinutes = isset($data['reminder_minutes']) ? (int) $data['reminder_minutes'] : 5;
            if (!in_array($reminderMinutes, [360, 60, 30, 15, 5], true)) {
                $errors['reminder_minutes'] = 'Недопустимое значение напоминания.';
            } else {
                $clean['reminder_minutes'] = $reminderMinutes;
            }
        }

        return [$errors, $clean];
    }

    public static function validateTheme(array $data): array
    {
        $errors = [];
        $themeMode = (string) ($data['theme_mode'] ?? 'system');
        $accentColor = (string) ($data['accent_color'] ?? 'blue');
        $weekStart = isset($data['week_start']) ? (int) $data['week_start'] : 1;

        if (!in_array($themeMode, ['light', 'dark', 'system'], true)) {
            $errors['theme_mode'] = 'Недопустимый режим темы.';
        }

        if (!in_array($accentColor, ['blue', 'purple', 'green', 'pink', 'orange', 'red', 'teal'], true)) {
            $errors['accent_color'] = 'Недопустимый акцентный цвет.';
        }

        if ($weekStart < 0 || $weekStart > 1) {
            $errors['week_start'] = 'Недопустимое значение первого дня недели.';
        }

        return [$errors, [
            'theme_mode' => $themeMode,
            'accent_color' => $accentColor,
            'week_start' => $weekStart,
        ]];
    }
}
