USE `bagmanov13_jarv`;

CREATE TABLE IF NOT EXISTS `files` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `type` VARCHAR(50) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `extension` VARCHAR(10) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_files_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `telegram_id` BIGINT NOT NULL,
  `telegram_username` VARCHAR(64) NULL,
  `first_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NULL,
  `display_name` VARCHAR(120) NULL,
  `avatar_file_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_telegram_id` (`telegram_id`),
  KEY `idx_users_avatar_file_id` (`avatar_file_id`),
  CONSTRAINT `fk_users_avatar_file` FOREIGN KEY (`avatar_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `theme_mode` ENUM('light','dark','system') NOT NULL DEFAULT 'system',
  `accent_color` VARCHAR(32) NOT NULL DEFAULT 'blue',
  `week_start` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_settings_user_id` (`user_id`),
  CONSTRAINT `fk_user_settings_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `task_date` DATE NOT NULL,
  `time_start` TIME NULL,
  `time_end` TIME NULL,
  `all_day` TINYINT(1) NOT NULL DEFAULT 0,
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `color` VARCHAR(32) NULL DEFAULT 'blue',
  `status` ENUM('active','completed','archived') NOT NULL DEFAULT 'active',
  `reminder_minutes` INT NOT NULL DEFAULT 5,
  `reminder_sent_at` DATETIME NULL,
  `position` INT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_user_date` (`user_id`, `task_date`),
  KEY `idx_tasks_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_tasks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(20) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `context_json` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_logs_level_created_at` (`level`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bot_user_states` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `chat_id` BIGINT NOT NULL,
  `pending_action_json` LONGTEXT NULL,
  `draft_request_json` LONGTEXT NULL,
  `context_json` LONGTEXT NULL,
  `last_interaction_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bot_user_states_user_id` (`user_id`),
  KEY `idx_bot_user_states_chat_id` (`chat_id`),
  CONSTRAINT `fk_bot_user_states_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bot_conversation_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role` VARCHAR(20) NOT NULL,
  `content` TEXT NOT NULL,
  `meta_json` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot_conversation_messages_user_id_id` (`user_id`, `id`),
  CONSTRAINT `fk_bot_conversation_messages_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
