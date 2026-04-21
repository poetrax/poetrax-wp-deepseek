<?php
namespace BM\Core\Controller;

use BM\Core\Service\BlockService;

class BlockController extends BaseController
{
    private BlockService $blockService;

    public function __construct()
    {
        $this->blockService = new BlockService();
    }

    /**
     * POST /api/blocks
     * Заблокировать пользователя
     */
    public function store(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $blockedId = (int) ($input['blocked_user_id'] ?? 0);
        $type = $input['type'] ?? 'profile';
        $reason = $input['reason'] ?? null;
        $days = isset($input['days']) ? (int) $input['days'] : null;
        
        if (!$blockedId) {
            $this->jsonError('Blocked user ID is required', 400);
            return;
        }
        
        try {
            $blockId = $this->blockService->blockUser($userId, $blockedId, $type, $reason, $days);
            $this->jsonResponse(['success' => true, 'block_id' => $blockId]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /api/blocks/{id}
     * Снять блокировку
     */
    public function delete(int $id): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $result = $this->blockService->unblock($id, $userId);
        
        if (!$result) {
            $this->jsonError('Block not found or you are not the blocker', 404);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'message' => 'Block removed']);
    }

    /**
     * GET /api/blocks/my
     * Мои блокировки (кого я заблокировал)
     */
    public function myBlocks(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $result = $this->blockService->getMyBlocks($userId, $page, $limit);
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/blocks/on-me
     * Кто меня заблокировал
     */
    public function blocksOnMe(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $result = $this->blockService->getBlocksOnMe($userId, $page, $limit);
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/blocks/check
     * Проверить, заблокирован ли пользователь
     */
    public function check(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $blockedId = (int) ($_GET['user_id'] ?? 0);
        $type = $_GET['type'] ?? 'profile';
        
        if (!$blockedId) {
            $this->jsonError('User ID is required', 400);
            return;
        }
        
        $isBlocked = $this->blockService->isBlocked($userId, $blockedId, $type);
        $this->jsonResponse(['success' => true, 'is_blocked' => $isBlocked]);
    }
}