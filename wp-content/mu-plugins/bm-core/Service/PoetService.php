<?php
namespace BM\Core\Service;

use BM\Core\Repository\PoetRepository;
use BM\Core\Repository\TrackRepository;
use BM\Core\Repository\PoemRepository;

class PoetService
{
    private PoetRepository $poetRepo;
    private TrackRepository $trackRepo;
    private PoemRepository $poemRepo;

    public function __construct()
    {
        $this->poetRepo = new PoetRepository();
        $this->trackRepo = new TrackRepository();
        $this->poemRepo = new PoemRepository();
    }

    /**
     * Получить поэта со всей статистикой
     */
    public function getPoetWithStats(int $poetId): ?object
    {
        $poet = $this->poetRepo->find($poetId);
        
        if (!$poet) {
            return null;
        }

        // Добавляем статистику
        $poet->stats = (object)[
            'poems_count' => $this->getPoemsCount($poetId),
            'tracks_count' => $this->getTracksCount($poetId),
            'popular_track' => $this->getPopularTrack($poetId)
        ];

        // Форматируем годы жизни
        $poet->years_life = $this->formatYears($poet);

        return $poet;
    }

    /**
     * Получить поэта по slug
     */
    public function getBySlug(string $slug): ?object
    {
        return $this->poetRepo->findBySlug($slug);
    }

    /**
     * Получить полную информацию для страницы поэта
     */
    public function getPoetPage(string $slug, int $limit = 12): array
    {
        $poet = $this->getBySlug($slug);
        
        if (!$poet) {
            return ['poet' => null, 'tracks' => [], 'poems' => []];
        }

        $tracks = $this->trackRepo->findBy(['poet_id' => $poet->id], $limit, 'created_at DESC');
        $poems = $this->poemRepo->findBy(['poet_id' => $poet->id], $limit, 'name');

        return [
            'poet' => $poet,
            'tracks' => $tracks,
            'poems' => $poems,
            'stats' => [
                'tracks_count' => $this->getTracksCount($poet->id),
                'poems_count' => $this->getPoemsCount($poet->id)
            ]
        ];
    }

    /**
     * Поиск по поэтам
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->poetRepo->searchByName($query, $limit);
    }

    /**
     * Получить популярных поэтов
     */
    public function getPopular(int $limit = 10): array
    {
        return $this->poetRepo->getPopular($limit);
    }

    /**
     * Получить случайных поэтов
     */
    public function getRandom(int $limit = 3): array
    {
        return $this->poetRepo->getRandom($limit);
    }

    /**
     * Создать нового поэта
     */
    public function createPoet(array $data): int
    {
        // Валидация
        if (empty($data['last_name'])) {
            throw new \InvalidArgumentException('Фамилия обязательна');
        }

        // Генерация slug, если не указан
        if (empty($data['poet_slug'])) {
            $data['poet_slug'] = $this->generateSlug(
                ($data['last_name'] ?? '') . ' ' .
                ($data['first_name'] ?? '') . ' ' .
                ($data['second_name'] ?? '')
            );
        }

        return $this->poetRepo->create($data);
    }

    /**
     * Вспомогательные методы
     */
    private function getPoemsCount(int $poetId): int
    {
        return $this->poemRepo->count(['poet_id' => $poetId]);
    }

    private function getTracksCount(int $poetId): int
    {
        return $this->trackRepo->count(['poet_id' => $poetId]);
    }

    private function getPopularTrack(int $poetId): ?object
    {
        // Здесь можно добавить логику получения популярного трека
        // Пока просто первый попавшийся
        $tracks = $this->trackRepo->findBy(['poet_id' => $poetId], 1);
        return $tracks[0] ?? null;
    }

    private function formatYears($poet): string
    {
        return $this->poetRepo->formatYears($poet);
    }

    private function generateSlug(string $string): string
    {
        $string = transliterator_transliterate("Russian-Latin/BGN", $string);
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        
        return trim($string, '-');
    }
	
	/**
 * Получить поэта по ID
 */
public function getById(int $poetId): ?object
{
    return $this->poetRepo->find($poetId);
}

/**
 * Получить всех поэтов с пагинацией
 */
public function getAll(int $page = 1, int $limit = 20): array
{
    return $this->poetRepo->getAll($page, $limit);
}
}
