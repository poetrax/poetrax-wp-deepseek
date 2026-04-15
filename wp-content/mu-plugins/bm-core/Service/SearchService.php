<?php
namespace BM\Core\Service;

use BM\Core\Repository\PoetRepository;
use BM\Core\Repository\TrackRepository;
use BM\Core\Repository\PoemRepository;
use BM\Core\Database\Connection;

class SearchService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    public function searchTracks(string $query, int $limit = 20): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM bm_ctbl000_track WHERE track_name LIKE ? LIMIT ?");
        $stmt->execute(["%$query%", $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function searchPoets(string $query, int $limit = 20): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM bm_ctbl000_poet WHERE poet_name LIKE ? LIMIT ?");
        $stmt->execute(["%$query%", $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function searchPoems(string $query, int $limit = 20): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM bm_ctbl000_poem WHERE name LIKE ? LIMIT ?");
        $stmt->execute(["%$query%", $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}