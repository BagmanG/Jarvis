<?php
namespace MiniApp\Repositories;

use MiniApp\Core\Database;
use PDO;

class BotStateRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bot_user_states WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByChatId(int $chatId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bot_user_states WHERE chat_id = :chat_id LIMIT 1');
        $stmt->execute(['chat_id' => $chatId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $userId, int $chatId, array $data = []): array
    {
        $existing = $this->getByUserId($userId);
        $pendingActionJson = array_key_exists('pending_action_json', $data) ? $data['pending_action_json'] : ($existing['pending_action_json'] ?? null);
        $draftRequestJson = array_key_exists('draft_request_json', $data) ? $data['draft_request_json'] : ($existing['draft_request_json'] ?? null);
        $contextJson = array_key_exists('context_json', $data) ? $data['context_json'] : ($existing['context_json'] ?? null);
        $lastInteractionAt = $data['last_interaction_at'] ?? now();

        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE bot_user_states SET chat_id = :chat_id, pending_action_json = :pending_action_json, draft_request_json = :draft_request_json, context_json = :context_json, last_interaction_at = :last_interaction_at, updated_at = :updated_at WHERE user_id = :user_id');
            $stmt->execute([
                'chat_id' => $chatId,
                'pending_action_json' => $pendingActionJson,
                'draft_request_json' => $draftRequestJson,
                'context_json' => $contextJson,
                'last_interaction_at' => $lastInteractionAt,
                'updated_at' => now(),
                'user_id' => $userId,
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO bot_user_states (user_id, chat_id, pending_action_json, draft_request_json, context_json, last_interaction_at, created_at, updated_at) VALUES (:user_id, :chat_id, :pending_action_json, :draft_request_json, :context_json, :last_interaction_at, :created_at, :updated_at)');
            $stmt->execute([
                'user_id' => $userId,
                'chat_id' => $chatId,
                'pending_action_json' => $pendingActionJson,
                'draft_request_json' => $draftRequestJson,
                'context_json' => $contextJson,
                'last_interaction_at' => $lastInteractionAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->getByUserId($userId);
    }

    public function clearPendingAction(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE bot_user_states SET pending_action_json = NULL, updated_at = :updated_at WHERE user_id = :user_id');
        $stmt->execute([
            'user_id' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function clearDraft(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE bot_user_states SET draft_request_json = NULL, updated_at = :updated_at WHERE user_id = :user_id');
        $stmt->execute([
            'user_id' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function addMessage(int $userId, string $role, string $content, array $meta = []): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO bot_conversation_messages (user_id, role, content, meta_json, created_at) VALUES (:user_id, :role, :content, :meta_json, :created_at)');
        $stmt->execute([
            'user_id' => $userId,
            'role' => substr($role, 0, 20),
            'content' => $content,
            'meta_json' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at' => now(),
        ]);
    }

    public function getRecentMessages(int $userId, int $limit = 12): array
    {
        $stmt = $this->pdo->prepare('SELECT role, content, meta_json, created_at FROM bot_conversation_messages WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        return array_reverse($rows);
    }
}
