<?php
namespace MiniApp\Services;

use MiniApp\Repositories\BotStateRepository;
use MiniApp\Repositories\TaskRepository;
use MiniApp\Repositories\UserRepository;
use MiniApp\Support\Logger;

class BotAssistantService
{
    private TelegramBotApi $telegram;
    private UserRepository $users;
    private TaskRepository $tasks;
    private BotStateRepository $state;
    private AiTunnelService $ai;

    public function __construct()
    {
        $this->telegram = new TelegramBotApi();
        $this->users = new UserRepository();
        $this->tasks = new TaskRepository();
        $this->state = new BotStateRepository();
        $this->ai = new AiTunnelService();
    }

    public function handleUpdate(array $update): void
    {
        if (!empty($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        if (empty($update['message'])) {
            return;
        }

        $message = $update['message'];
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $telegramUser = $message['from'] ?? [];
        if ($chatId <= 0 || empty($telegramUser['id'])) {
            return;
        }

        $user = $this->users->createOrUpdateFromTelegram($telegramUser);
        $this->state->upsert((int) $user['id'], $chatId);

        if (!empty($message['text']) && trim((string) $message['text']) === '/start') {
            $this->state->clearPendingAction((int) $user['id']);
            $this->state->clearDraft((int) $user['id']);
            $this->telegram->sendMessage($chatId, $this->buildWelcomeMessage($user));
            return;
        }

        if (!empty($message['text']) && trim((string) $message['text']) === '/today') {
            $this->sendTaskList($chatId, (int) $user['id'], 'today', 1);
            return;
        }

        if (!empty($message['text']) && trim((string) $message['text']) === '/week') {
            $this->sendTaskList($chatId, (int) $user['id'], 'week', 1);
            return;
        }

        $incomingText = null;
        if (!empty($message['voice'])) {
            $incomingText = $this->transcribeVoiceMessage($message);
            if (!$incomingText) {
                $this->telegram->sendMessage($chatId, 'Не удалось распознать голосовое сообщение. Попробуй отправить его ещё раз или напиши текстом.');
                return;
            }
            $this->telegram->sendMessage($chatId, 'Я распознал голосовое сообщение так: ' . "\n\n<i>" . htmlspecialchars($incomingText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</i>');
        } elseif (!empty($message['text'])) {
            $incomingText = trim((string) $message['text']);
        }

        if (!$incomingText) {
            $this->telegram->sendMessage($chatId, 'Пока я умею работать с текстом, командами и голосовыми сообщениями.');
            return;
        }

        $this->handleNaturalLanguage($user, $chatId, $incomingText);
    }

    private function handleNaturalLanguage(array $user, int $chatId, string $text): void
    {
        $this->state->addMessage((int) $user['id'], 'user', $text);
        $state = $this->state->getByUserId((int) $user['id']);
        $draft = !empty($state['draft_request_json']) ? json_decode((string) $state['draft_request_json'], true) : null;
        $pendingAction = !empty($state['pending_action_json']) ? json_decode((string) $state['pending_action_json'], true) : null;

        if ($pendingAction && preg_match('/^(отмена|cancel|нет)$/ui', $text)) {
            $this->state->clearPendingAction((int) $user['id']);
            $this->telegram->sendMessage($chatId, 'Действие отменено.');
            return;
        }

        $plan = $this->buildPlan($user, $text, is_array($draft) ? $draft : null);
        if (!$plan) {
            $fallback = 'Я не смог корректно обработать запрос. Попробуй сформулировать его чуть подробнее, например: “добавь задачу на завтра в 14:00 созвон с командой”.';
            $this->state->addMessage((int) $user['id'], 'assistant', $fallback);
            $this->telegram->sendMessage($chatId, $fallback);
            return;
        }

        $mode = (string) ($plan['mode'] ?? 'reply');
        $reply = trim((string) ($plan['reply'] ?? ''));
        $action = is_array($plan['action'] ?? null) ? $plan['action'] : null;

        if ($mode === 'clarify') {
            $this->state->upsert((int) $user['id'], $chatId, [
                'draft_request_json' => json_encode($action ?: $plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $this->state->addMessage((int) $user['id'], 'assistant', $reply, ['mode' => 'clarify']);
            $this->telegram->sendMessage($chatId, $reply ?: 'Уточни, пожалуйста, недостающие детали.');
            return;
        }

        $this->state->clearDraft((int) $user['id']);

        if ($mode === 'confirm' && $action) {
            $resolvedAction = $this->resolveAction($user, $action);
            if (($resolvedAction['needs_clarification'] ?? false) === true) {
                $message = (string) ($resolvedAction['clarification_message'] ?? 'Уточни, пожалуйста, детали.');
                $this->state->upsert((int) $user['id'], $chatId, [
                    'draft_request_json' => json_encode($resolvedAction['draft_action'] ?? $action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
                $this->telegram->sendMessage($chatId, $message);
                return;
            }

            $this->state->upsert((int) $user['id'], $chatId, [
                'pending_action_json' => json_encode($resolvedAction, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $confirmationText = $reply ?: $this->buildConfirmationText($resolvedAction);
            $this->state->addMessage((int) $user['id'], 'assistant', $confirmationText, ['mode' => 'confirm']);
            $this->telegram->sendMessage($chatId, $confirmationText, [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Подтвердить', 'callback_data' => 'confirm:yes'],
                            ['text' => 'Отклонить', 'callback_data' => 'confirm:no'],
                        ],
                    ],
                ],
            ]);
            return;
        }

        if ($mode === 'reply') {
            if (($action['type'] ?? null) === 'list_tasks') {
                $range = (string) ($action['range'] ?? 'today');
                if ($reply) {
                    $this->telegram->sendMessage($chatId, $reply);
                }
                if ($range === 'date' && !empty($action['date'])) {
                    $textAndMarkup = $this->buildSpecificDateListPayload((int) $user['id'], (string) $action['date']);
                    $this->telegram->sendMessage($chatId, $textAndMarkup['text'], ['reply_markup' => $textAndMarkup['reply_markup']]);
                } else {
                    $this->sendTaskList($chatId, (int) $user['id'], $range === 'week' ? 'week' : 'today', 1);
                }
                return;
            }
            $this->state->addMessage((int) $user['id'], 'assistant', $reply, ['mode' => 'reply']);
            $this->telegram->sendMessage($chatId, $reply ?: 'Готово.');
            return;
        }

        $this->telegram->sendMessage($chatId, $reply ?: 'Я понял запрос, но не смог безопасно его выполнить. Давай уточним детали.');
    }

    private function buildPlan(array $user, string $text, ?array $draft): ?array
    {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));
        $snapshotTasks = $this->tasks->getRange((int) $user['id'], date('Y-m-d', strtotime('-2 days')), date('Y-m-d', strtotime('+14 days')));
        $recentMessages = $this->state->getRecentMessages((int) $user['id'], 8);

        $systemPrompt = "Ты — очень внимательный Telegram-ассистент по задачам. Твоя задача — понять, что хочет пользователь, и ответить СТРОГО JSON-объектом.\n"
            . "Сегодня: {$today}. Сейчас на сервере: {$now}. Часовой пояс сервера: " . config('app.timezone', 'UTC') . ".\n"
            . "Ты умеешь: создавать задачи, удалять задачи, изменять задачи, показывать задачи, отвечать на вопросы по задачам.\n"
            . "Если не хватает данных для создания/изменения/удаления — mode=clarify и задай уточняющий вопрос.\n"
            . "Все опасные действия create/update/delete должны идти в mode=confirm.\n"
            . "Разрешено создавать или удалять несколько задач за один запрос.\n"
            . "Если пользователь просит показать задачи на сегодня/неделю/дату — mode=reply, action.type=list_tasks.\n"
            . "Если пользователь хочет общую помощь или разговор — mode=reply без action либо с informative action.\n"
            . "Формат JSON: {\"mode\":\"clarify|confirm|reply\",\"reply\":\"текст\",\"action\":{...}}.\n"
            . "Для create_many используй action={\"type\":\"create_tasks\",\"items\":[{\"title\":...,\"description\":...,\"date\":\"YYYY-MM-DD|null\",\"time_start\":\"HH:MM|null\",\"time_end\":\"HH:MM|null\",\"all_day\":true|false|null}]}\n"
            . "Для delete используй action={\"type\":\"delete_tasks\",\"items\":[{\"title\":\"...\",\"date\":\"YYYY-MM-DD|null\"}]}\n"
            . "Для update используй action={\"type\":\"update_tasks\",\"selector\":{\"title\":\"...\",\"date\":\"YYYY-MM-DD|null\"},\"changes\":{...}}\n"
            . "Для list используй action={\"type\":\"list_tasks\",\"range\":\"today|week|date\",\"date\":\"YYYY-MM-DD|null\"}.\n"
            . "Если пользователь уточняет прошлый незавершённый запрос, используй draft_request ниже.\n"
            . "Не придумывай поля, которых нет. reply должен быть по-русски.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'system', 'content' => 'draft_request=' . json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ['role' => 'system', 'content' => 'recent_messages=' . json_encode($recentMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ['role' => 'system', 'content' => 'task_snapshot_until_' . $weekEnd . '=' . json_encode($snapshotTasks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ['role' => 'user', 'content' => $text],
        ];

        return $this->ai->plan($messages);
    }

    private function resolveAction(array $user, array $action): array
    {
        $type = (string) ($action['type'] ?? '');
        if ($type === 'create_tasks') {
            $items = $action['items'] ?? [];
            if (!is_array($items) || !$items) {
                return ['needs_clarification' => true, 'clarification_message' => 'Не вижу задач для добавления. Опиши их ещё раз.'];
            }

            $normalized = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $title = trim((string) ($item['title'] ?? ''));
                $date = $item['date'] ?? null;
                if ($title === '' || !$date) {
                    return [
                        'needs_clarification' => true,
                        'clarification_message' => 'Для добавления задачи мне нужны хотя бы название и дата. Уточни, пожалуйста, когда именно нужно выполнить задачу.',
                        'draft_action' => $action,
                    ];
                }
                $normalized[] = [
                    'title' => $title,
                    'description' => isset($item['description']) ? trim((string) $item['description']) : null,
                    'task_date' => (string) $date,
                    'time_start' => !empty($item['time_start']) ? (string) $item['time_start'] . ':00' : null,
                    'time_end' => !empty($item['time_end']) ? (string) $item['time_end'] . ':00' : null,
                    'all_day' => array_key_exists('all_day', $item) && $item['all_day'] !== null ? (!empty($item['all_day']) ? 1 : 0) : (empty($item['time_start']) ? 1 : 0),
                    'priority' => 'medium',
                    'color' => 'blue',
                    'status' => 'active',
                    'reminder_minutes' => 5,
                ];
            }

            return [
                'type' => 'create_tasks',
                'items' => $normalized,
            ];
        }

        if ($type === 'delete_tasks') {
            $items = $action['items'] ?? [];
            if (!is_array($items) || !$items) {
                return ['needs_clarification' => true, 'clarification_message' => 'Не понял, какую именно задачу удалить.'];
            }

            $resolvedItems = [];
            foreach ($items as $item) {
                $title = trim((string) ($item['title'] ?? ''));
                $date = !empty($item['date']) ? (string) $item['date'] : null;
                if ($title === '') {
                    return ['needs_clarification' => true, 'clarification_message' => 'Уточни название задачи, которую нужно удалить.'];
                }
                $matches = $this->tasks->searchByTitle((int) $user['id'], $title, $date, 10);
                if (!$matches) {
                    return ['needs_clarification' => true, 'clarification_message' => 'Не нашёл задачу “' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '”. Проверь название или дату.'];
                }
                if (count($matches) > 1) {
                    return ['needs_clarification' => true, 'clarification_message' => $this->buildAmbiguousTasksMessage('delete', $title, $matches), 'draft_action' => $action];
                }
                $resolvedItems[] = $matches[0];
            }

            return [
                'type' => 'delete_tasks',
                'items' => $resolvedItems,
            ];
        }

        if ($type === 'update_tasks') {
            $selector = is_array($action['selector'] ?? null) ? $action['selector'] : [];
            $changes = is_array($action['changes'] ?? null) ? $action['changes'] : [];
            $title = trim((string) ($selector['title'] ?? ''));
            $date = !empty($selector['date']) ? (string) $selector['date'] : null;
            if ($title === '') {
                return ['needs_clarification' => true, 'clarification_message' => 'Уточни, какую задачу изменить.'];
            }
            if (!$changes) {
                return ['needs_clarification' => true, 'clarification_message' => 'Уточни, что именно нужно изменить в задаче.'];
            }
            $matches = $this->tasks->searchByTitle((int) $user['id'], $title, $date, 10);
            if (!$matches) {
                return ['needs_clarification' => true, 'clarification_message' => 'Не нашёл задачу для изменения.'];
            }
            if (count($matches) > 1) {
                return ['needs_clarification' => true, 'clarification_message' => $this->buildAmbiguousTasksMessage('update', $title, $matches), 'draft_action' => $action];
            }
            $normalizedChanges = [];
            foreach (['title', 'description', 'date'] as $field) {
                if (array_key_exists($field, $changes)) {
                    $normalizedChanges[$field === 'date' ? 'task_date' : $field] = $changes[$field];
                }
            }
            foreach (['time_start', 'time_end'] as $field) {
                if (array_key_exists($field, $changes)) {
                    $normalizedChanges[$field] = $changes[$field] ? $changes[$field] . ':00' : null;
                }
            }
            if (array_key_exists('all_day', $changes)) {
                $normalizedChanges['all_day'] = !empty($changes['all_day']) ? 1 : 0;
            }
            return [
                'type' => 'update_tasks',
                'item' => $matches[0],
                'changes' => $normalizedChanges,
            ];
        }

        if ($type === 'list_tasks') {
            return $action;
        }

        return $action;
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = (string) ($callbackQuery['id'] ?? '');
        $data = (string) ($callbackQuery['data'] ?? '');
        $message = $callbackQuery['message'] ?? [];
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $messageId = (int) ($message['message_id'] ?? 0);
        $telegramUser = $callbackQuery['from'] ?? [];
        if ($chatId <= 0 || $messageId <= 0 || empty($telegramUser['id'])) {
            return;
        }

        $user = $this->users->createOrUpdateFromTelegram($telegramUser);
        $this->state->upsert((int) $user['id'], $chatId);

        if ($data === 'confirm:no') {
            $this->state->clearPendingAction((int) $user['id']);
            $this->telegram->answerCallbackQuery($callbackId, 'Отменено');
            $this->telegram->editMessageReplyMarkup($chatId, $messageId, ['inline_keyboard' => []]);
            $this->telegram->sendMessage($chatId, 'Действие отклонено.');
            return;
        }

        if ($data === 'confirm:yes') {
            $state = $this->state->getByUserId((int) $user['id']);
            $pendingAction = !empty($state['pending_action_json']) ? json_decode((string) $state['pending_action_json'], true) : null;
            if (!is_array($pendingAction)) {
                $this->telegram->answerCallbackQuery($callbackId, 'Действие не найдено', true);
                return;
            }
            $this->telegram->answerCallbackQuery($callbackId, 'Выполняю');
            $this->telegram->editMessageReplyMarkup($chatId, $messageId, ['inline_keyboard' => []]);
            $resultText = $this->executeConfirmedAction($user, $pendingAction);
            $this->state->clearPendingAction((int) $user['id']);
            $this->state->addMessage((int) $user['id'], 'assistant', $resultText, ['mode' => 'executed']);
            $this->telegram->sendMessage($chatId, $resultText);
            return;
        }

        if (preg_match('/^list:(today|week):(\d+)$/', $data, $matches)) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->renderTaskListMessage($chatId, $messageId, (int) $user['id'], $matches[1], (int) $matches[2]);
            return;
        }

        if (preg_match('/^detail:(\d+):(today|week):(\d+)$/', $data, $matches)) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->renderTaskDetailMessage($chatId, $messageId, (int) $user['id'], (int) $matches[1], $matches[2], (int) $matches[3]);
            return;
        }

        if (preg_match('/^back:(today|week):(\d+)$/', $data, $matches)) {
            $this->telegram->answerCallbackQuery($callbackId);
            $this->renderTaskListMessage($chatId, $messageId, (int) $user['id'], $matches[1], (int) $matches[2]);
            return;
        }
    }

    private function executeConfirmedAction(array $user, array $action): string
    {
        $type = (string) ($action['type'] ?? '');
        if ($type === 'create_tasks') {
            $created = [];
            foreach ((array) ($action['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $created[] = $this->tasks->create((int) $user['id'], $item);
            }
            if (!$created) {
                return 'Не удалось добавить задачи.';
            }
            $lines = ['✅ Задачи добавлены:'];
            foreach ($created as $task) {
                $lines[] = '• ' . $this->formatTaskShort($task);
            }
            return implode("\n", $lines);
        }

        if ($type === 'delete_tasks') {
            $deleted = [];
            foreach ((array) ($action['items'] ?? []) as $task) {
                if (!is_array($task) || empty($task['id'])) {
                    continue;
                }
                if ($this->tasks->softDelete((int) $user['id'], (int) $task['id'])) {
                    $deleted[] = $task;
                }
            }
            if (!$deleted) {
                return 'Не удалось удалить задачи.';
            }
            $lines = ['🗑 Задачи удалены:'];
            foreach ($deleted as $task) {
                $lines[] = '• ' . $this->formatTaskShort($task);
            }
            return implode("\n", $lines);
        }

        if ($type === 'update_tasks') {
            $item = is_array($action['item'] ?? null) ? $action['item'] : null;
            $changes = is_array($action['changes'] ?? null) ? $action['changes'] : [];
            if (!$item || empty($item['id'])) {
                return 'Не удалось определить задачу для изменения.';
            }
            $updated = $this->tasks->update((int) $user['id'], (int) $item['id'], $changes);
            if (!$updated) {
                return 'Не удалось изменить задачу.';
            }
            return '✏️ Задача обновлена: ' . $this->formatTaskShort($updated);
        }

        if ($type === 'list_tasks') {
            $range = (string) ($action['range'] ?? 'today');
            $label = $range === 'week' ? 'week' : 'today';
            $this->sendTaskList((int) $user['telegram_id'], (int) $user['id'], $label, 1);
            return 'Показал список задач.';
        }

        return 'Готово.';
    }

    private function sendTaskList(int $chatId, int $userId, string $range, int $page): void
    {
        $textAndMarkup = $this->buildTaskListPayload($userId, $range, $page);
        $this->telegram->sendMessage($chatId, $textAndMarkup['text'], [
            'reply_markup' => $textAndMarkup['reply_markup'],
        ]);
    }

    private function renderTaskListMessage(int $chatId, int $messageId, int $userId, string $range, int $page): void
    {
        $textAndMarkup = $this->buildTaskListPayload($userId, $range, $page);
        $this->telegram->editMessageText($chatId, $messageId, $textAndMarkup['text'], [
            'reply_markup' => $textAndMarkup['reply_markup'],
        ]);
    }

    private function renderTaskDetailMessage(int $chatId, int $messageId, int $userId, int $taskId, string $backRange, int $backPage): void
    {
        $task = $this->tasks->findById($userId, $taskId);
        if (!$task) {
            $this->telegram->editMessageText($chatId, $messageId, 'Задача не найдена.', [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [['text' => 'Назад', 'callback_data' => 'back:' . $backRange . ':' . $backPage]],
                    ],
                ],
            ]);
            return;
        }

        $this->telegram->editMessageText($chatId, $messageId, $this->buildTaskDetailText($task), [
            'reply_markup' => [
                'inline_keyboard' => [
                    [['text' => 'Назад', 'callback_data' => 'back:' . $backRange . ':' . $backPage]],
                ],
            ],
        ]);
    }

    private function buildTaskListPayload(int $userId, string $range, int $page): array
    {
        $perPage = 5;
        $page = max(1, $page);
        if ($range === 'week') {
            $from = date('Y-m-d');
            $to = date('Y-m-d', strtotime('+6 days'));
            $items = $this->tasks->getRange($userId, $from, $to);
            $title = '📅 Задачи на неделю';
        } else {
            $from = date('Y-m-d');
            $to = $from;
            $items = $this->tasks->getByDate($userId, $from);
            $title = '🗓 Задачи на сегодня';
        }

        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

        $lines = [$title, ''];
        if (!$slice) {
            $lines[] = 'Список пуст.';
        } else {
            foreach ($slice as $task) {
                $lines[] = '• ' . $this->formatTaskShort($task);
            }
        }
        $lines[] = '';
        $lines[] = 'Страница ' . $page . ' из ' . $pages;

        $keyboard = [];
        foreach ($slice as $task) {
            $keyboard[] = [
                ['text' => $this->trimButtonText($this->taskTitleForButton($task)), 'callback_data' => 'detail:' . $task['id'] . ':' . $range . ':' . $page],
            ];
        }

        $navRow = [];
        if ($page > 1) {
            $navRow[] = ['text' => '⬅️', 'callback_data' => 'list:' . $range . ':' . ($page - 1)];
        }
        if ($page < $pages) {
            $navRow[] = ['text' => '➡️', 'callback_data' => 'list:' . $range . ':' . ($page + 1)];
        }
        if ($navRow) {
            $keyboard[] = $navRow;
        }

        return [
            'text' => implode("\n", $lines),
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ];
    }


    private function buildSpecificDateListPayload(int $userId, string $date): array
    {
        $items = $this->tasks->getByDate($userId, $date);
        $lines = ['🗓 Задачи на ' . date('d.m.Y', strtotime($date)), ''];
        if (!$items) {
            $lines[] = 'Список пуст.';
        } else {
            foreach ($items as $task) {
                $lines[] = '• ' . $this->formatTaskShort($task);
            }
        }

        $keyboard = [];
        foreach ($items as $task) {
            $keyboard[] = [
                ['text' => $this->trimButtonText($this->taskTitleForButton($task)), 'callback_data' => 'detail:' . $task['id'] . ':today:1'],
            ];
        }

        return [
            'text' => implode("
", $lines),
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ];
    }

    private function buildTaskDetailText(array $task): string
    {
        $lines = [
            '📌 <b>' . htmlspecialchars((string) $task['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>',
            '',
            'Дата: ' . htmlspecialchars(date('d.m.Y', strtotime((string) $task['date'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'Время: ' . htmlspecialchars($this->humanTime($task), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'Статус: ' . htmlspecialchars((string) $task['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'Приоритет: ' . htmlspecialchars((string) $task['priority'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ];
        if (!empty($task['description'])) {
            $lines[] = 'Описание: ' . htmlspecialchars((string) $task['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return implode("\n", $lines);
    }

    private function buildWelcomeMessage(array $user): string
    {
        $name = htmlspecialchars((string) ($user['display_name'] ?? 'друг'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "Привет, {$name}!\n\nЯ твой ИИ-помощник по задачам. Могу помочь вести список дел, показывать задачи на сегодня и неделю, а позже — создавать, изменять и удалять задачи по обычным сообщениям и голосу.\n\nКоманды:\n/today — задачи на сегодня\n/week — задачи на неделю\n\nМожно писать и просто текстом: “добавь задачу на завтра в 14:00 созвон с командой”.";
    }

    private function buildConfirmationText(array $action): string
    {
        $type = (string) ($action['type'] ?? '');
        if ($type === 'create_tasks') {
            $lines = ['Подтвердите добавление задач:'];
            foreach ((array) ($action['items'] ?? []) as $item) {
                $lines[] = '• ' . $this->formatTaskShort($item);
            }
            return implode("\n", $lines);
        }
        if ($type === 'delete_tasks') {
            $lines = ['Подтвердите удаление задач:'];
            foreach ((array) ($action['items'] ?? []) as $task) {
                $lines[] = '• ' . $this->formatTaskShort($task);
            }
            return implode("\n", $lines);
        }
        if ($type === 'update_tasks') {
            $item = (array) ($action['item'] ?? []);
            $lines = ['Подтвердите изменение задачи:', '• ' . $this->formatTaskShort($item), '', 'Изменения:'];
            foreach ((array) ($action['changes'] ?? []) as $key => $value) {
                $lines[] = '• ' . $key . ': ' . ($value === null ? 'очистить' : (is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
            }
            return implode("\n", $lines);
        }
        return 'Подтвердите действие.';
    }

    private function formatTaskShort(array $task): string
    {
        $title = trim((string) ($task['title'] ?? 'Без названия'));
        $date = (string) ($task['date'] ?? $task['task_date'] ?? '');
        $dateText = $date ? date('d.m.Y', strtotime($date)) : 'без даты';
        $timeStart = $task['time_start'] ?? null;
        $timeEnd = $task['time_end'] ?? null;
        if (is_string($timeStart) && strlen($timeStart) >= 5) {
            $timeStart = substr($timeStart, 0, 5);
        }
        if (is_string($timeEnd) && strlen($timeEnd) >= 5) {
            $timeEnd = substr($timeEnd, 0, 5);
        }
        $time = !empty($task['all_day']) ? 'весь день' : ($timeStart ? ($timeEnd ? $timeStart . '-' . $timeEnd : $timeStart) : 'без времени');
        return $title . ' — ' . $dateText . ', ' . $time;
    }

    private function humanTime(array $task): string
    {
        if (!empty($task['all_day'])) {
            return 'Весь день';
        }
        $start = $task['time_start'] ?? null;
        $end = $task['time_end'] ?? null;
        if ($start && $end) {
            return $start . ' - ' . $end;
        }
        if ($start) {
            return (string) $start;
        }
        return 'Без времени';
    }

    private function buildAmbiguousTasksMessage(string $action, string $title, array $matches): string
    {
        $verb = $action === 'delete' ? 'удалить' : 'изменить';
        $lines = ['Нашёл несколько задач с похожим названием “' . $title . '”. Уточни, какую именно нужно ' . $verb . ':'];
        foreach ($matches as $task) {
            $lines[] = '• ' . $this->formatTaskShort($task);
        }
        return implode("\n", $lines);
    }

    private function taskTitleForButton(array $task): string
    {
        $time = !empty($task['all_day']) ? 'весь день' : ($task['time_start'] ?: 'без времени');
        return (string) $task['title'] . ' (' . $time . ')';
    }

    private function trimButtonText(string $text): string
    {
        return mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text;
    }

    private function transcribeVoiceMessage(array $message): ?string
    {
        $voice = $message['voice'] ?? [];
        $fileId = (string) ($voice['file_id'] ?? '');
        if ($fileId === '') {
            return null;
        }
        $file = $this->telegram->getFile($fileId);
        if (empty($file['file_path'])) {
            return null;
        }
        $binary = $this->telegram->downloadFile((string) $file['file_path']);
        if (!is_string($binary) || $binary === '') {
            return null;
        }
        $filename = basename((string) $file['file_path']);
        $mimeType = !empty($voice['mime_type']) ? (string) $voice['mime_type'] : 'audio/ogg';
        return $this->ai->transcribe($filename, $binary, $mimeType);
    }
}
