<?php
namespace BM\Core\Repository;
use BM\Core\Database\Cache;
use BM\Core\Database\Connection;
use BM\Core\Database\QueryBuilder;
use PDO;

abstract class AbstractRepository {
    protected Connection $connection;
    protected Cache $cache;
    protected QueryBuilder $querybuilder;
    protected PDO $pdo;

    public function __construct(Connection $connection, Cache $cache, QueryBuilder $querybuilder) {
        $this->connection = $connection;
        $this->cache = $cache;
        $this->querybuilder = $querybuilder;
        $this->pdo = $this->connection->getPDO();
    }
    
    protected function getConnection(): Connection {
        return $this->connection;
    }
    
    protected function getCache(): Cache {
        return $this->cache;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->querybuilder;
    }

    protected function getPdo(): PDO
    {
        return $this->pdo;
    }
}

 /*
abstract class AbstractRepository {
    protected Connection $connection;
    protected string $tableName;
    /*
    // Добавляем поддержку дополнительных параметров
    abstract public function delete($id, $userId = null){
        //TODO
    }
    abstract public function create($data): int{
        //TODO
    }
    abstract public function find($id){
        //TODO
    }
    abstract public function getById($id, $userId = null){
        //TODO
    }
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
*/