<?php
namespace BM\Cache;

interface CacheInterface {
    public function get(string $key);
    public function set(string $key, $data, ?int $ttl = null): bool;
    public function delete(string $key): bool;
    public function clear(?string $pattern = null): bool;
    public function has(string $key): bool;
}