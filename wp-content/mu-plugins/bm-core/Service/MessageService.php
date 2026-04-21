<?php
namespace BM\Core\Service;

use BM\Core\Repository\MessageRepository;
use BM\Core\Service\BlockService;

class MessageService
{
    private MessageRepository $messageRepo;
    private BlockService $blockService;

    public function __construct()
    {
        $this->messageRepo = new MessageRepository();
        $this->blockService = new BlockService();
    }

    /**
     * Отправить сообщение
     */
    public function send(int $fromId, int $toId, string $message, ?string $subject = null): int
    {
        // Нельзя отправить сообщение самому себе
        if ($fromId === $toId) {
            throw new \InvalidArgumentException('Cannot send message to yourself');
        }
        
        // Проверка блокировок
        if (!$this->blockService->canSendMessage($fromId, $toId)) {
            throw new \Exception('You cannot send message to this user', 403);
        }
        
        // Ограничение длины
        if (strlen($message) > 5000) {
            throw new \InvalidArgumentException('Message too long (max 5000 chars)');
        }
        
        return $this->messageRepo->send($fromId, $toId, $message, $subject);
    }

    /**
     * Получить входящие
     */
    public function getInbox(int $userId, int $page = 1, int $limit = 20): array
    {
        return $this->messageRepo->getInbox($userId, $page, $limit);
    }

    /**
     * Получить отправленные
     */
    public function getSent(int $userId, int $page = 1, int $limit = 20): array
    {
        return $this->messageRepo->getSent($userId, $page, $limit);
    }

    /**
     * Получить сообщение
     */
    public function getMessage(int $messageId, int $userId): ?array
    {
        $message = $this->messageRepo->getById($messageId, $userId);
        
        // Если получатель читает — отмечаем как прочитанное
        if ($message && $message['to_user_id'] == $userId && !$message['is_read']) {
            $this->messageRepo->markAsRead($messageId, $userId);
            $message['is_read'] = 1;
        }
        
        return $message;
    }

    /**
     * Удалить сообщение
     */
    public function delete(int $messageId, int $userId): bool
    {
        return $this->messageRepo->delete($messageId, $userId) > 0;
    }

    /**
     * Получить количество непрочитанных
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->messageRepo->getUnreadCount($userId);
    }
}