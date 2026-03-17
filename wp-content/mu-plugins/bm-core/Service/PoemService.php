<?php
namespace BM\Core\Service;

use BM\Core\Repository\PoemRepository;
use BM\Core\Repository\PoetRepository;
use BM\Core\Repository\TrackRepository;

class PoemService
{
    private PoemRepository $poemRepo;
    private PoetRepository $poetRepo;
    private TrackRepository $trackRepo;

    public function __construct()
    {
        $this->poemRepo = new PoemRepository();
        $this->poetRepo = new PoetRepository();
        $this->trackRepo = new TrackRepository();
    }

    /**
     * Получить стихотворение с полной информацией
     */
    public function getPoemWithDetails(int $poemId): ?object
    {
        $poem = $this->poemRepo->find($poemId);
        
        if (!$poem) {
            return null;
        }

        // Добавляем информацию о поэте
        if ($poem->poet_id) {
            $poem->poet = $this->poetRepo->find($poem->poet_id);
        }

        // Добавляем треки на это стихотворение
        $poem->tracks = $this->trackRepo->findBy(['poem_id' => $poemId], 10, 'created_at DESC');
        $poem->tracks_count = count($poem->tracks);

        return $poem;
    }

    /**
     * Получить стихотворение по slug
     */
    public function getBySlug(string $slug): ?object
    {
        return $this->poemRepo->findBySlug($slug);
    }

    /**
     * Получить стихи поэта
     */
    public function getByPoet(int $poetId, int $limit = 20): array
    {
        return $this->poemRepo->findByPoet($poetId, $limit);
    }

    /**
     * Получить страницу стихотворения
     */
    public function getPoemPage(string $slug): array
    {
        $poem = $this->getBySlug($slug);
        
        if (!$poem) {
            return ['poem' => null, 'tracks' => []];
        }

        // Получаем полную информацию
        $poemWithDetails = $this->getPoemWithDetails($poem->id);

        return [
            'poem' => $poemWithDetails,
            'tracks' => $poemWithDetails->tracks ?? []
        ];
    }

    /**
     * Поиск по стихам
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->poemRepo->search($query, $limit);
    }

    /**
     * Получить популярные стихи (по количеству треков)
     */
    public function getPopular(int $limit = 10): array
    {
        return $this->poemRepo->getPopular($limit);
    }

    /**
     * Получить последние добавленные стихи
     */
    public function getRecent(int $limit = 10): array
    {
        return $this->poemRepo->getRecent($limit);
    }

    /**
     * Создать новое стихотворение
     */
    public function createPoem(array $data): int
    {
        // Валидация
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Название стихотворения обязательно');
        }

        if (empty($data['poet_id'])) {
            throw new \InvalidArgumentException('ID поэта обязателен');
        }

        // Генерация slug, если не указан
        if (empty($data['poem_slug'])) {
            $data['poem_slug'] = $this->generateSlug($data['name']);
        }

        return $this->poemRepo->create($data);
    }

    /**
     * Обновить стихотворение
     */
    public function updatePoem(int $id, array $data): bool
    {
        // Если меняется название, обновляем slug
        if (isset($data['name']) && empty($data['poem_slug'])) {
            $data['poem_slug'] = $this->generateSlug($data['name']);
        }

        return (bool) $this->poemRepo->update($id, $data);
    }

    /**
     * Удалить стихотворение (с проверкой на наличие треков)
     */
    public function deletePoem(int $id): bool
    {
        // Проверяем, есть ли треки на это стихотворение
        $tracks = $this->trackRepo->findBy(['poem_id' => $id], 1);
        
        if (!empty($tracks)) {
            throw new \RuntimeException('Нельзя удалить стихотворение, на которое есть треки');
        }

        return $this->poemRepo->delete($id);
    }

    /**
     * Вспомогательные методы
     */
    private function generateSlug(string $string): string
    {
        $string = transliterator_transliterate("Russian-Latin/BGN", $string);
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        
        return trim($string, '-');
    }
}
