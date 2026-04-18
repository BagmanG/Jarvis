ALTER TABLE `tasks`
  ADD COLUMN `reminder_minutes` INT NOT NULL DEFAULT 5 AFTER `status`,
  ADD COLUMN `reminder_sent_at` DATETIME NULL AFTER `reminder_minutes`;
