<?php
namespace BM\Core\Database;

use PDO;
use PDOException;

class Loader
{
    private Connection $connection;
    private Cache $cache;
    private int $dictionaryRowLimit;
    private int $cacheRowLimit;
    private int $cacheTtl;
	private array $config;
 
    public function __construct(Connection $connection, Cache $cache, array $config)
    {
        $this->connection = $connection;
        $this->cache = $cache;
 		$this->config = $config;
		$this->dictionaryRowLimit = $this->config['database']['dictionary_row_limit'] ?? 1000;
		$this->cacheRowLimit = $this->config['database']['cache_row_limit'] ?? 5000;
		$this->cacheTtl = $this->config['cache']['ttl'] ?? 3600;
    }

    public function loadAllTables(): void
    {
        try {
            $pdo = $this->connection->getPDO();
            $stmt = $pdo->query("
                SELECT TABLE_NAME, TABLE_ROWS 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
            ");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tables as $tableInfo) {
                $this->loadTable($tableInfo['TABLE_NAME'], (int) $tableInfo['TABLE_ROWS']);
            }
        } catch (PDOException $e) {
            throw new PDOException("Failed to load tables: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function loadTable(string $table, int $rowCount): void
    {
        try {
            $pdo = $this->connection->getPDO();
            if ($rowCount < $this->dictionaryRowLimit) {
                $data = $pdo->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
                $this->cache->set("table:{$table}:full", $data, $this->cacheTtl);
            } else {
                $data = $pdo->query("SELECT * FROM {$table} LIMIT {$this->cacheRowLimit}")->fetchAll(PDO::FETCH_ASSOC);
                $this->cache->set("table:{$table}:sample", $data, $this->cacheTtl);
            }
        } catch (PDOException $e) {
            throw new PDOException("Failed to load table '{$table}': " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getTableData(string $table, bool $full = false): ?array
    {
        $key = $full ? "table:{$table}:full" : "table:{$table}:sample";
        return $this->cache->get($key);
    }

    public function warmupCache(): void
    {
        $this->loadAllTables();
    }

    public function clearTableCache(?string $table = null): void
    {
        if ($table === null) {
            $this->cache->flushByPrefix('table:');
        } else {
            $this->cache->delete("table:{$table}:full");
            $this->cache->delete("table:{$table}:sample");
        }
    }
}