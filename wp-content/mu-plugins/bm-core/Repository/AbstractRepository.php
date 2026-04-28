namespace BM\Core\Repository;

abstract class AbstractRepository {
    protected Connection $connection;
    protected string $tableName;
    
    // Добавляем поддержку дополнительных параметров
    abstract public function delete($id, $userId = null);
    abstract public function create($data): int;
    abstract public function find($id);
    abstract public function getById($id, $userId = null);
}

// Тогда BlockRepository:
class BlockRepository extends AbstractRepository {
    public function delete($id, $userId = null): bool {
        if ($userId !== null) {
            // Удаление с проверкой пользователя
            $result = $this->connection->delete(
                $this->getTableName(), 
                "id = $id AND blocker_user_id = $userId"
            );
        } else {
            $result = $this->connection->delete($this->getTableName(), "id = $id");
        }
        return $result > 0;
    }
    
    public function create($data): int {
        return $this->connection->insert($this->getTableName(), $data);
    }
}