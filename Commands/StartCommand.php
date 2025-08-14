<?php
namespace Bot\Commands;

use Bot\Core\Command;
use Bot\Core\Messages;
use Bot\Core\Images;
class StartCommand extends Command {
    public function execute(int $chatId, string $text): void {
        // Messages::sendTextAndPhoto(
        //     $chatId,
        //     "Привет! Это стартовое сообщение с фотографией.",
        //     "https://example.com/path/to/your/image.jpg"
        // );
        Messages::sendText($chatId,Images::welcomeImage());
    }
}