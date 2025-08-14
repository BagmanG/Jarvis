<?php
namespace Bot\Core;

use Bot\Commands\DefaultCommand;
use Bot\Commands\SayCommand;

class Router {
    private $commandsMap;
    
    public function __construct(array $commandsMap) {
        $this->commandsMap = $commandsMap;
    }
    
    public function resolve(?string $text): Command {
        if ($text === null) {
            return new DefaultCommand();
        }
        
        foreach ($this->commandsMap as $command => $class) {
            if (strpos($text, $command) === 0) {
                $className = "Bot\\Commands\\{$class}";
                return new $className();
            }
        }
        
        if (stripos($text, 'скажи') !== false) {
            return new SayCommand();
        }
        
        return new DefaultCommand();
    }
}