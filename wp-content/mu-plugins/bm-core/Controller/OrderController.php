<?php
namespace BM\Core\Controller;

use BM\Core\Service\OrderService;

class OrderController extends BaseController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    /**
     * GET /api/orders
     * Список заказов
     */
    public function index(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $orders = $this->orderService->getUserOrders($userId, $page, $limit);
        $this->jsonResponse(['success' => true, 'data' => $orders]);
    }

    /**
     * GET /api/orders/{id}
     * Детали заказа
     */
    public function show(int $id): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $order = $this->orderService->getOrder($id, $userId);
        
        if (!$order) {
            $this->jsonError('Order not found', 404);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'data' => $order]);
    }

    /**
     * POST /api/orders
     * Оформить заказ
     */
    public function store(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $shippingAddress = $input['shipping_address'] ?? [];
        $paymentMethod = $input['payment_method'] ?? 'yookassa';
        $customerNote = $input['customer_note'] ?? null;
        
        if (empty($shippingAddress)) {
            $this->jsonError('Shipping address is required', 400);
            return;
        }
        
        try {
            $result = $this->orderService->createOrder($userId, $shippingAddress, $paymentMethod, $customerNote);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}