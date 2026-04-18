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
