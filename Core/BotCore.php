<?php
namespace Bot\Core;
class BotCore {
    protected $update;
    protected $config;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->update = json_decode(file_get_contents('php://input'), true);
    }
    
    public function getMessage(): ?array {
        return $this->update['message'] ?? null;
    }
    
    public function getChatId(): ?int {
        return $this->getMessage()['chat']['id'] ?? null;
    }
    
    public function getText(): ?string {
        return $this->getMessage()['text'] ?? null;
    }
    
    public function run(): void {
        if (!$this->getMessage()) return;
        $router = new Router($this->config['commands']);
        $command = $router->resolve($this->getText());
        $command->execute($this->getChatId(), $this->getText());
    }
}