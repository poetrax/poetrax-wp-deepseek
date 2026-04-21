<?php
namespace BM\Core\Repository;

use BM\Core\Database\TableMapper;

class CartRepository extends AbstractRepository
{
    private const TABLE = 'cart';

    protected function getTableName(): string
    {
        return TableMapper::getInstance()->get(self::TABLE);
    }

    /**
     * Получить корзину пользователя
     */
    public function getCart(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE user_id = ?";
        return $this->connection->fetchOne($sql, [$userId]);
    }

    /**
     * Создать корзину
     */
    public function createCart(int $userId): int
    {
        $data = ['user_id' => $userId];
        return $this->connection->insert($this->getTableName(), $data);
    }

    /**
     * Получить или создать корзину
     */
    public function getOrCreateCart(int $userId): array
    {
        $cart = $this->getCart($userId);
        if (!$cart) {
            $cartId = $this->createCart($userId);
            return ['id' => $cartId, 'user_id' => $userId];
        }
        return $cart;
    }

    /**
     * Получить все элементы корзины
     */
    public function getItems(int $cartId): array
    {
        $sql = "SELECT ci.*, p.name as product_name, p.slug as product_slug, p.price as current_price,
                       pv.name as variant_name
                FROM bm_ctbl000_cart_item ci
                JOIN bm_ctbl000_product p ON ci.product_id = p.id
                LEFT JOIN bm_ctbl000_product_variant pv ON ci.variant_id = pv.id
                WHERE ci.cart_id = ?";
        return $this->connection->fetchAll($sql, [$cartId]);
    }

    /**
     * Добавить товар в корзину
     */
    public function addItem(int $cartId, int $productId, int $quantity, ?int $variantId = null, float $price): int
    {
        // Проверяем, есть ли уже такой товар
        $sql = "SELECT id, quantity FROM bm_ctbl000_cart_item 
                WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))";
        $existing = $this->connection->fetchOne($sql, [$cartId, $productId, $variantId, $variantId]);
        
        if ($existing) {
            // Обновляем количество
            $newQuantity = $existing['quantity'] + $quantity;
            $updateSql = "UPDATE bm_ctbl000_cart_item SET quantity = ? WHERE id = ?";
            $stmt = $this->connection->getPdo()->prepare($updateSql);
            $stmt->execute([$newQuantity, $existing['id']]);
            return $existing['id'];
        }
        
        // Добавляем новый
        $data = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => $price
        ];
        return $this->connection->insert('bm_ctbl000_cart_item', $data);
    }

    /**
     * Обновить количество товара в корзине
     */
    public function updateItemQuantity(int $itemId, int $quantity): int
    {
        if ($quantity <= 0) {
            return $this->removeItem($itemId);
        }
        
        $sql = "UPDATE bm_ctbl000_cart_item SET quantity = ? WHERE id = ?";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([$quantity, $itemId]);
        return $stmt->rowCount();
    }

    /**
     * Удалить товар из корзины
     */
    public function removeItem(int $itemId): int
    {
        $sql = "DELETE FROM bm_ctbl000_cart_item WHERE id = ?";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([$itemId]);
        return $stmt->rowCount();
    }

    /**
     * Очистить корзину
     */
    public function clearCart(int $cartId): int
    {
        $sql = "DELETE FROM bm_ctbl000_cart_item WHERE cart_id = ?";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute([$cartId]);
        return $stmt->rowCount();
    }
}