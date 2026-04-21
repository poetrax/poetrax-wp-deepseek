<?php
namespace BM\Core\Service;

use BM\Core\Repository\OrderRepository;
use BM\Core\Repository\CartRepository;
use BM\Core\Repository\ProductRepository;

class OrderService
{
    private OrderRepository $orderRepo;
    private CartRepository $cartRepo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->cartRepo = new CartRepository();
        $this->productRepo = new ProductRepository();
    }

    /**
     * Оформить заказ из корзины
     */
    public function createOrder(int $userId, array $shippingAddress, string $paymentMethod, ?string $customerNote = null): array
    {
        // Получаем корзину
        $cart = $this->cartRepo->getCart($userId);
        if (!$cart) {
            throw new \Exception('Cart is empty', 400);
        }
        
        $items = $this->cartRepo->getItems($cart['id']);
        if (empty($items)) {
            throw new \Exception('Cart is empty', 400);
        }
        
        // Проверка наличия товаров
        foreach ($items as $item) {
            if (!$this->productRepo->checkStock($item['product_id'], $item['quantity'], $item['variant_id'])) {
                throw new \Exception("Product {$item['product_name']} is out of stock", 400);
            }
        }
        
        // Расчёт итоговой суммы
        $subtotal = array_sum(array_map(function($item) {
            return $item['quantity'] * $item['price'];
        }, $items));
        
        // Создание заказа
        $orderData = [
            'user_id' => $userId,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'subtotal' => $subtotal,
            'shipping_cost' => 0,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'shipping_address' => json_encode($shippingAddress),
            'customer_note' => $customerNote
        ];
        
        $orderId = $this->orderRepo->createOrder($orderData);
        
        // Добавление товаров в заказ
        foreach ($items as $item) {
            $product = $this->productRepo->getById($item['product_id']);
            $variant = null;
            if ($item['variant_id']) {
                $variants = $this->productRepo->getVariants($item['product_id']);
                foreach ($variants as $v) {
                    if ($v['id'] == $item['variant_id']) {
                        $variant = $v;
                        break;
                    }
                }
            }
            
            $orderItem = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'product_name' => $product['name'],
                'variant_name' => $variant ? $variant['name'] : null,
                'sku' => $variant ? $variant['sku'] : $product['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
            
            $this->orderRepo->addOrderItem($orderId, $orderItem);
            
            // Списание остатков
            $this->productRepo->updateStock($item['product_id'], $item['quantity'], $item['variant_id']);
        }
        
        // Очистка корзины
        $this->cartRepo->clearCart($cart['id']);
        
        $order = $this->orderRepo->getById($orderId, $userId);
        
        return [
            'order' => $order,
            'order_number' => $order['order_number']
        ];
    }

    /**
     * Получить заказы пользователя
     */
    public function getUserOrders(int $userId, int $page = 1, int $limit = 20): array
    {
        return $this->orderRepo->getUserOrders($userId, $page, $limit);
    }

    /**
     * Получить детали заказа
     */
    public function getOrder(int $orderId, int $userId): ?array
    {
        return $this->orderRepo->getById($orderId, $userId);
    }
}