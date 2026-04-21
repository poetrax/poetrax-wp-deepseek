<?php
namespace BM\Core\Controller;

use BM\Core\Service\CartService;

class CartController extends BaseController
{
    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    /**
     * GET /api/cart
     * Получить корзину
     */
    public function index(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $cart = $this->cartService->getCart($userId);
        $this->jsonResponse(['success' => true, 'data' => $cart]);
    }

    /**
     * POST /api/cart/items
     * Добавить товар в корзину
     */
    public function addItem(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $productId = (int) ($input['product_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);
        $variantId = isset($input['variant_id']) ? (int) $input['variant_id'] : null;
        
        if (!$productId || $quantity <= 0) {
            $this->jsonError('Invalid product or quantity', 400);
            return;
        }
        
        try {
            $cart = $this->cartService->addItem($userId, $productId, $quantity, $variantId);
            $this->jsonResponse(['success' => true, 'data' => $cart]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * PUT /api/cart/items/{id}
     * Обновить количество товара
     */
    public function updateItem(int $id): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $quantity = (int) ($input['quantity'] ?? 0);
        
        if ($quantity < 0) {
            $this->jsonError('Invalid quantity', 400);
            return;
        }
        
        try {
            $cart = $this->cartService->updateItem($userId, $id, $quantity);
            $this->jsonResponse(['success' => true, 'data' => $cart]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * DELETE /api/cart/items/{id}
     * Удалить товар из корзины
     */
    public function removeItem(int $id): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        try {
            $cart = $this->cartService->removeItem($userId, $id);
            $this->jsonResponse(['success' => true, 'data' => $cart]);
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * DELETE /api/cart
     * Очистить корзину
     */
    public function clear(): void
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            $this->jsonError('Unauthorized', 401);
            return;
        }
        
        $cart = $this->cartService->clearCart($userId);
        $this->jsonResponse(['success' => true, 'data' => $cart]);
    }
}