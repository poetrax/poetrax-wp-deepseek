<?php
namespace BM\Core\Controller;

use BM\Core\Service\AccessService;

class ServiceController extends BaseController
{
    private AccessService $accessService;

    public function __construct()
    {
        $this->accessService = new AccessService();
    }

    /**
     * GET /api/services
     * Список услуг
     */
    public function index(): void
    {
        $userId = $this->getCurrentUserId();
        $services = $this->accessService->getUserAccesses($userId);
        
        $this->jsonResponse(['success' => true, 'data' => $services]);
    }

    /**
     * POST /api/services/{slug}/buy
     * Купить доступ
     */
    public function buy(string $slug): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $paymentMethod = $input['payment_method'] ?? 'points';
        
        try {
            $result = $this->accessService->purchaseAccess($userId, $slug, $paymentMethod);
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/services/check?service=listen
     * Проверить доступ к услуге
     */
    public function check(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $serviceSlug = $_GET['service'] ?? '';
        if (!$serviceSlug) {
            $this->jsonError('Service parameter required', 400);
            return;
        }
        
        $hasAccess = $this->accessService->hasAccess($userId, $serviceSlug);
        $this->jsonResponse(['success' => true, 'has_access' => $hasAccess]);
    }
}