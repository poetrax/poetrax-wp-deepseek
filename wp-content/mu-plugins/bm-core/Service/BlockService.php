<?php
namespace BM\Core\Service;

use BM\Core\Repository\BlockRepository;

class BlockService
{
    private BlockRepository $blockRepo;

    public function __construct()
    {
        $this->blockRepo = new BlockRepository();
    }

    /**
     * Заблокировать пользователя
     */
    public function blockUser(int $blockerId, int $blockedId, string $type, ?string $reason = null, ?int $days = null): int
    {
        // Нельзя заблокировать самого себя
        if ($blockerId === $blockedId) {
            throw new \InvalidArgumentException('Cannot block yourself');
        }
        
        $data = [
            'blocker_user_id' => $blockerId,
            'blocked_user_id' => $blockedId,
            'block_type' => $type,
            'reason' => $reason,
            'expires_at' => $days ? date('Y-m-d H:i:s', strtotime("+$days days")) : null
        ];
        
        return $this->blockRepo->create($data);
    }

    /**
     * Проверить, заблокирован ли пользователь
     */
    public function isBlocked(int $blockerId, int $blockedId, string $type = 'profile'): bool
    {
        return $this->blockRepo->exists($blockerId, $blockedId, $type);
    }

    /**
     * Проверить, заблокирован ли трек для пользователя
     */
    public function isTrackBlocked(int $userId, int $trackId, int $authorId): bool
    {
        // Пользователь заблокировал автора
        if ($this->isBlocked($userId, $authorId, 'profile')) {
            return true;
        }
        
        // Автор заблокировал пользователя от просмотра трека
        if ($this->isBlocked($authorId, $userId, 'show_track_to_user', $trackId)) {
            return true;
        }
        
        // Пользователь заблокировал этот трек
        if ($this->isBlocked($userId, $authorId, 'hide_track_from_user', $trackId)) {
            return true;
        }
        
        return false;
    }

    /**
     * Проверить, может ли пользователь оставлять комментарии
     */
    public function canComment(int $userId, int $authorId): bool
    {
        return !$this->isBlocked($authorId, $userId, 'comment');
    }

    /**
     * Проверить, может ли пользователь отправлять сообщения
     */
    public function canSendMessage(int $fromId, int $toId): bool
    {
        // Нельзя отправлять сообщения, если получатель заблокировал отправителя
        if ($this->isBlocked($toId, $fromId, 'message')) {
            return false;
        }
        
        // Нельзя отправлять сообщения, если отправитель заблокировал получателя
        if ($this->isBlocked($fromId, $toId, 'message')) {
            return false;
        }
        
        return true;
    }

    /**
     * Получить мои блокировки (кого я заблокировал)
     */
    public function getMyBlocks(int $userId, int $page = 1, int $limit = 20): array
    {
        return $this->blockRepo->getByBlocker($userId, $page, $limit);
    }

    /**
     * Получить блокировки на меня (кто меня заблокировал)
     */
    public function getBlocksOnMe(int $userId, int $page = 1, int $limit = 20): array
    {
        return $this->blockRepo->getByBlocked($userId, $page, $limit);
    }

    /**
     * Снять блокировку
     */
    public function unblock(int $blockId, int $userId): bool
    {
        return $this->blockRepo->delete($blockId, $userId);
    }

    /**
     * Снять все блокировки между пользователями
     */
    public function unblockAll(int $blockerId, int $blockedId): int
    {
        return $this->blockRepo->deleteAll($blockerId, $blockedId);
    }
}