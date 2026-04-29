<?php
namespace BM\Core\Database;

class Cache
{
    private static ?self $instance = null;
    private array $storage = [];
    private array $ttl = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Преобразует ключ в строку (если передан массив)
     */
    private function normalizeKey($key): string
    {
        if (is_array($key)) {
            return implode('_', $key);
        }
        return (string) $key;
    }

    public function get(string $key): mixed
    {
        $key = $this->normalizeKey($key);
        if (!$this->has($key))
            return null;
        if (isset($this->ttl[$key]) && $this->ttl[$key] < time()) {
            $this->delete($key);
            return null;
        }
        return $this->storage[$key];
    }
 
    public function set(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $key = $this->normalizeKey($key);
        $this->storage[$key] = $value;
        if ($ttlSeconds !== null) {
            $this->ttl[$key] = time() + $ttlSeconds;
        }
    }

    public function has(string $key): bool
    {
        $key = $this->normalizeKey($key);
        if (!isset($this->storage[$key]))
            return false;
        if (isset($this->ttl[$key]) && $this->ttl[$key] < time()) {
            $this->delete($key);
            return false;
        }
        return true;
    }

    public function delete(string $key): void
    {
        $key = $this->normalizeKey($key);
        unset($this->storage[$key], $this->ttl[$key]);
    }

    public function flushAll(): void
    {
        $this->storage = [];
        $this->ttl = [];
    }

    public function flushByPrefix(string $prefix): void
    {
        foreach (array_keys($this->storage) as $key) {
            if (str_starts_with($key, $prefix)) {
                $this->delete($key);
            }
        }
    }
}