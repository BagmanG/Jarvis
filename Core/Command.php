<?php
namespace Bot\Core;

abstract class Command {
    abstract public function execute(int $chatId, string $text): void;
    
    protected function containsWord(string $text, string $word): bool {
        return stripos($text, $word) !== false;
    }
}