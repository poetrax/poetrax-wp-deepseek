<?php
namespace BM\Core\Repository;

use BM\Core\Repository\AbstractRepository;

class BalanceRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'user_balance';
    }
    
    public function getBalance(int $userId): int
    {
        $sql = "SELECT credits FROM {$this->getTableName()} WHERE user_id = :user_id";
        $result = $this->connection->fetchOne($sql, ['user_id' => $userId]);
        return $result ? (int)$result->credits : 0;
    }
    
    public function addCredits(int $userId, int $amount, string $description = ''): bool
    {
        $this->connection->beginTransaction();
        
        try {
            $current = $this->getBalance($userId);
            $newBalance = $current + $amount;
            
            // Обновляем баланс
            $sql = "INSERT INTO {$this->getTableName()} (user_id, credits, lifetime_earned)
                    VALUES (:user_id, :credits, :amount)
                    ON CONFLICT (user_id) DO UPDATE
                    SET credits = :credits,
                        lifetime_earned = lifetime_earned + :amount,
                        updated_at = NOW()";
            
            $this->connection->query($sql, [
                'user_id' => $userId,
                'credits' => $newBalance,
                'amount' => $amount
            ]);
            
            // Записываем транзакцию
            $this->recordTransaction($userId, $amount, $newBalance, 'earn', $description);
            
            $this->connection->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
    
    public function spendCredits(int $userId, int $amount, string $description = ''): bool
    {
        $current = $this->getBalance($userId);
        if ($current < $amount) {
            throw new \Exception('Недостаточно средств');
        }
        
        $this->connection->beginTransaction();
        
        try {
            $newBalance = $current - $amount;
            
            $sql = "UPDATE {$this->getTableName()} 
                    SET credits = :credits,
                        lifetime_spent = lifetime_spent + :amount,
                        updated_at = NOW()
                    WHERE user_id = :user_id AND credits >= :amount";
            
            $updated = $this->connection->query($sql, [
                'user_id' => $userId,
                'credits' => $newBalance,
                'amount' => $amount
            ])->rowCount();
            
            if (!$updated) {
                throw new \Exception('Ошибка при списании');
            }
            
            $this->recordTransaction($userId, -$amount, $newBalance, 'spend', $description);
            
            $this->connection->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
    
    private function recordTransaction($userId, $amount, $balanceAfter, $type, $description)
    {
        $sql = "INSERT INTO credit_transactions 
                (user_id, amount, balance_after, transaction_type, description)
                VALUES (:user_id, :amount, :balance_after, :type, :description)";
        
        return $this->connection->query($sql, [
            'user_id' => $userId,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'type' => $type,
            'description' => $description
        ]);
    }
}