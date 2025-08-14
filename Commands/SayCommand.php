<?php
namespace Bot\Commands;

use Bot\Core\Command;
use Bot\Core\Messages;

class SayCommand extends Command {
    public function execute(int $chatId, string $text): void {
        Messages::sendText($chatId, "не скажу");
    }
}