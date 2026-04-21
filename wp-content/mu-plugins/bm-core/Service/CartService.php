<?php
namespace BM\Core\Service;

use BM\Core\Repository\CartRepository;
use BM\Core\Repository\ProductRepository;

class CartService
{
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        $this->cartRepo = new CartRepository();
        $this->productRepo = new ProductRepository();
    }

    /**
     * Получить корзину пользователя
     */
    public function getCart(int $userId): array
    {
        $cart = $this->cartRepo->getOrCreateCart($userId);
        $items = $this->cartRepo->getItems($cart['id']);
        
        $subtotal = 0;
        foreach ($items as &$item) {
            $item['total'] = $item['quantity'] * $item['price'];
            $subtotal += $item['total'];
        }
        
        return [
            'id' => $cart['id'],
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'items_count' => count($items)
        ];
    }

    /**
     * Добавить товар в корзину
     */
    public function addItem(int $userId, int $productId, int $quantity, ?int $variantId = null): array
    {
        // Проверка наличия товара
        if (!$this->productRepo->checkStock($productId, $quantity, $variantId)) {
            throw new \Exception('Product out of stock', 400);
        }
        
        $product = $this->productRepo->getById($productId);
        if (!$product) {
            throw new \Exception('Product not found', 404);
        }
        
        $cart = $this->cartRepo->getOrCreateCart($userId);
        $price = $variantId ? $this->getVariantPrice($productId, $variantId) : $product['price'];
        
        $itemId = $this->cartRepo->addItem($cart['id'], $productId, $quantity, $variantId, $price);
        
        return $this->getCart($userId);
    }

    /**
     * Обновить количество товара
     */
    public function updateItem(int $userId, int $itemId, int $quantity): array
    {
        $cart = $this->cartRepo->getCart($userId);
        if (!$cart) {
            throw new \Exception('Cart not found', 404);
        }
        
        $this->cartRepo->updateItemQuantity($itemId, $quantity);
        
        return $this->getCart($userId);
    }

    /**
     * Удалить товар из корзины
     */
    public function removeItem(int $userId, int $itemId): array
    {
        $cart = $this->cartRepo->getCart($userId);
        if (!$cart) {
            throw new \Exception('Cart not found', 404);
        }
        
        $this->cartRepo->removeItem($itemId);
        
        return $this->getCart($userId);
    }

    /**
     * Очистить корзину
     */
    public function clearCart(int $userId): array
    {
        $cart = $this->cartRepo->getCart($userId);
        if ($cart) {
            $this->cartRepo->clearCart($cart['id']);
        }
        
        return $this->getCart($userId);
    }

    private function getVariantPrice(int $productId, int $variantId): float
    {
        $variants = $this->productRepo->getVariants($productId);
        foreach ($variants as $variant) {
            if ($variant['id'] == $variantId) {
                return $variant['price'] ?? $this->productRepo->getById($productId)['price'];
            }
        }
        return $this->productRepo->getById($productId)['price'];
    }
}