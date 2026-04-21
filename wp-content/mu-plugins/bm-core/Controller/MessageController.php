<?php
namespace BM\Core\Controller;

use BM\Core\Service\MessageService;

class MessageController extends BaseController
{
    private MessageService $messageService;

    public function __construct()
    {
        $this->messageService = new MessageService();
    }

    /**
     * GET /api/messages/inbox
     * Входящие
     */
    public function inbox(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $result = $this->messageService->getInbox($userId, $page, $limit);
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/messages/sent
     * Отправленные
     */
    public function sent(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $result = $this->messageService->getSent($userId, $page, $limit);
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/messages/{id}
     * Просмотр сообщения
     */
    public function show(int $id): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $message = $this->messageService->getMessage($id, $userId);
        
        if (!$message) {
            $this->jsonError('Message not found', 404);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'data' => $message]);
    }

    /**
     * POST /api/messages
     * Отправить сообщение
     */
    public function store(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $toId = (int) ($input['to_user_id'] ?? 0);
        $subject = $input['subject'] ?? null;
        $message = $input['message'] ?? '';
        
        if (!$toId) {
            $this->jsonError('Recipient user ID is required', 400);
            return;
        }
        
        if (empty($message)) {
            $this->jsonError('Message text is required', 400);
            return;
        }
        
        try {
            $messageId = $this->messageService->send($userId, $toId, $message, $subject);
            $this->jsonResponse(['success' => true, 'message_id' => $messageId]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 403);
        }
    }

    /**
     * DELETE /api/messages/{id}
     * Удалить сообщение
     */
    public function delete(int $id): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $result = $this->messageService->delete($id, $userId);
        
        if (!$result) {
            $this->jsonError('Message not found or already deleted', 404);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'message' => 'Message deleted']);
    }

    /**
     * GET /api/messages/unread/count
     * Количество непрочитанных
     */
    public function unreadCount(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $count = $this->messageService->getUnreadCount($userId);
        $this->jsonResponse(['success' => true, 'unread_count' => $count]);
    }
}