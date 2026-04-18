# Telegram Calendar Mini App

Готовый Telegram Mini App календаря в iOS-стилистике: SPA на HTML/CSS/Vanilla JS + REST API на чистом PHP 7.4 + MySQL.

## Что внутри
- `app.php` — точка входа Mini App
- `api/v1/index.php` — REST API
- `bot.php` — webhook-вход для Telegram бота
- `assets/` — фронтенд (стили + JS)
- `src/` — backend-логика и роутинг
- `uploads/avatars/` — загружаемые аватары
- `sql/install.sql` — создание базы и таблиц
- `.htaccess` — rewrite для красивых API URL

## Почему идентификатор пользователя = telegram_id
Я использовал `telegram_id`, а не `username`:
- `telegram_id` стабилен и не меняется
- `username` пользователь может изменить или вообще не иметь
- при этом `telegram_username` сохраняется и показывается в профиле

## Требования сервера
- PHP 7.4+
- MySQL / MariaDB
- Apache с `mod_rewrite`
- желательно `PDO`, `fileinfo`, `gd`

## Установка
1. Загрузите архив на хостинг.
2. Импортируйте `sql/install.sql` в MySQL.
3. Откройте `config/config.php` и заполните:
   - данные БД
   - `telegram.bot_token`
   - `app.token_secret`
   - при необходимости `app.base_url`
4. Убедитесь, что папка `uploads/avatars/` доступна на запись.
5. В BotFather укажите URL Mini App на `https://ваш-домен/app.php`

## Важно про Telegram авторизацию
Backend проверяет подпись `initData` от Telegram. Без корректного bot token авторизация работать не будет.

Для локальной отладки можно временно включить:
```php
'debug' => true,
```
Тогда frontend сможет зайти с demo-пользователем вне Telegram.

## API
Основные endpoints:
- `POST /api/v1/auth/telegram`
- `GET /api/v1/bootstrap`
- `GET /api/v1/profile`
- `PATCH /api/v1/profile`
- `POST /api/v1/profile/avatar`
- `DELETE /api/v1/profile/avatar`
- `GET /api/v1/settings`
- `PATCH /api/v1/settings/theme`
- `GET /api/v1/calendar/month?year=YYYY&month=MM`
- `GET /api/v1/tasks?date=YYYY-MM-DD`
- `GET /api/v1/tasks/range?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `GET /api/v1/tasks/{id}`
- `POST /api/v1/tasks`
- `PATCH /api/v1/tasks/{id}`
- `PATCH /api/v1/tasks/{id}/status`
- `DELETE /api/v1/tasks/{id}`

## Архитектура
### Frontend
- SPA без перезагрузок
- bottom sheets для задачи / профиля / перехода по месяцам
- темы `light / dark / system`
- акцентные цвета
- плавные анимации, loader, skeleton, toast

### Backend
- чистый PHP без composer и фреймворков
- PDO + prepared statements
- верификация Telegram WebApp `initData`
- мягкое удаление задач
- загрузка и валидация аватаров

## Если API не открывается
Проверьте:
- включён ли `mod_rewrite`
- работает ли `.htaccess`
- корректны ли права на папки uploads
- заполнен ли `bot_token`
- совпадает ли домен Mini App с тем, который вы реально открываете

## Что можно улучшить дальше
- drag & drop / reorder задач
- повторяющиеся события
- push/reminder-уведомления
- категории и фильтры
- поиск
- полноценный day/week/list режим как в Apple Calendar


## Telegram Bot webhook
1. Укажите в `config/config.php`:
   - `telegram.bot_token`
   - `ai.api_key`
   - `ai.chat_model = gpt-4o-mini`
   - `ai.transcription_model = whisper-1`
2. Выполните SQL из `sql/upgrade_bot.sql` если база уже установлена.
3. Настройте webhook у Telegram на URL вида `https://ваш-домен/bot.php`
4. Бот поддерживает:
   - `/start`
   - `/today`
   - `/week`
   - голосовые сообщения через Whisper
   - подтверждение create/update/delete через inline-кнопки
   - пагинацию задач кнопками

### Что уже реализовано в боте
- автоматическое создание пользователя при `/start`
- показ задач на сегодня и неделю
- просмотр полной информации о задаче по нажатию на кнопку
- распознавание голосовых сообщений
- ИИ-парсинг запросов на создание, удаление и изменение задач
- подтверждение перед важными действиями
- уточняющие вопросы, если данных недостаточно или задача неоднозначна

### Ограничения текущей версии
- для update/delete неоднозначных задач бот просит уточнить вручную
- список задач на конкретную дату пока без отдельной пагинации назад/вперёд
- для надёжной работы нужен `curl` в PHP
