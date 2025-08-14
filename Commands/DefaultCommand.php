<?php
namespace Bot\Commands;

use Bot\Core\Command;
use Bot\Core\Messages;

class DefaultCommand extends Command {
    public function execute(int $chatId, string $text): void {
        Messages::sendText($chatId, "Извините, я вас не понял.");
    }
}