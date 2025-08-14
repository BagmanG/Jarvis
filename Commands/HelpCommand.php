<?php
namespace Bot\Commands;

use Bot\Core\Command;
use Bot\Core\Messages;

class HelpCommand extends Command {
    public function execute(int $chatId, string $text): void {
        $helpText = "Это справочное сообщение.\nДоступные команды:\n/start - начать работу\n/help - получить помощь";
        Messages::sendText($chatId, $helpText);
    }
}