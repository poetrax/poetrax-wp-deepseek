<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class OrderRepository extends AbstractRepository
{
    private const TABLE = 'order';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Создать заказ
     */
    public function createOrder(array $data): int
    {
        $data['order_number'] = $this->generateOrderNumber();
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Получить заказ по ID
     */
    public function getById(int $orderId, int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE id = ? AND user_id = ?";
        return $this->connection->fetchOne($sql, [$orderId, $userId]);
    }

    /**
     * Получить заказ по номеру
     */
    public function getByOrderNumber(string $orderNumber): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE order_number = ?";
        return $this->connection->fetchOne($sql, [$orderNumber]);
    }

    /**
     * Получить заказы пользователя
     */
    public function getUserOrders(int $userId, int $page = 1, int $limit = 20): array
    {
        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE user_id = ? 
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?";
        
        $items = $this->connection->fetchAll($sql, [$userId, $limit, $offset]);
        
        $countSql = "SELECT COUNT(*) FROM {$this->getTableName()} WHERE user_id = ?";
        $total = (int) $this->connection->fetchOne($countSql, [$userId])['COUNT(*)'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Добавить товар в заказ
     */
    public function addOrderItem(int $orderId, array $item): int
    {
        $data = [
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'product_name' => $item['product_name'],
            'variant_name' => $item['variant_name'] ?? null,
            'sku' => $item['sku'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'total' => $item['quantity'] * $item['price']
        ];
        
        return $this->connection->insert('bm_ctbl000_order_item', $data);
    }

    /**
     * Обновить статус заказа
     */
    public function updateStatus(int $orderId, string $status): int
    {
        return $this->connection->update($this->getTableName(), ['status' => $status], "id = $orderId");
    }

    /**
     * Обновить статус оплаты
     */
    public function updatePaymentStatus(int $orderId, string $paymentId): int
    {
        return $this->connection->update(
            $this->getTableName(),
            ['payment_id' => $paymentId, 'status' => 'paid'],
            "id = $orderId"
        );
    }

    /**
     * Генерация номера заказа
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}