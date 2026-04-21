<?php
namespace BM\Core\Service;

use BM\Core\Repository\TrackRepository;
use BM\Core\Repository\PoetRepository;
use BM\Core\Repository\PoemRepository;
use BM\Core\Repository\InteractionRepository;
use BM\Core\Service\BlockService;

class TrackService
{
    private TrackRepository $trackRepo;
    private PoetRepository $poetRepo;
    private PoemRepository $poemRepo;
    private InteractionRepository $interactionRepo;
	private $config;  

   public function __construct($config = null)
	{
    $this->config = $config;
    $this->trackRepo = new TrackRepository($config);
    $this->poetRepo = new PoetRepository();
    $this->poemRepo = new PoemRepository();
    $this->interactionRepo = new InteractionRepository();
	}
		

	public function getTrackRepo()
	{
		return $this->trackRepo;
	}

    /**
     * Получить трек со всей связанной информацией
     */
    public function getTrackWithDetails(int $trackId): ?object
    {
        $track = $this->trackRepo->find($trackId);
        
        if (!$track) {
            return null;
        }

        // Добавляем информацию о поэте
        if ($track->poet_id) {
            $track->poet = $this->poetRepo->find($track->poet_id);
        }

        // Добавляем информацию о стихотворении
        if ($track->poem_id) {
            $track->poem = $this->poemRepo->find($track->poem_id);
        }

        // Добавляем статистику взаимодействий
        $track->stats = $this->interactionRepo->getTrackStats($trackId);

        return $track;
    }

    /**
     * Получить рекомендации на основе трека
     */
    public function getRecommendations(int $trackId, int $limit = 5): array
    {
        $track = $this->trackRepo->find($trackId);
        
        if (!$track) {
            return [];
        }

        $recommendations = [];
        $excludeIds = [$trackId];

        // 1. Треки того же поэта
        if ($track->poet_id) {
            $byPoet = $this->trackRepo->findBy(['poet_id' => $track->poet_id], $limit * 2);
            
            foreach ($byPoet as $t) {
                if (!in_array($t->id, $excludeIds)) {
                    $t->recommendation_reason = 'Тот же поэт';
                    $recommendations[] = $t;
                    $excludeIds[] = $t->id;
                    
                    if (count($recommendations) >= $limit) {
                        break;
                    }
                }
            }
        }

        // 2. Популярные треки (если не хватило)
        if (count($recommendations) < $limit) {
            $popular = $this->trackRepo->getPopular($limit * 2);
            
            foreach ($popular as $t) {
                if (!in_array($t->id, $excludeIds)) {
                    $t->recommendation_reason = 'Популярное';
                    $recommendations[] = $t;
                    
                    if (count($recommendations) >= $limit) {
                        break;
                    }
                }
            }
        }

        return $recommendations;
    }

    /**
     * Создать новый трек
     */
    public function createTrack(array $data): int
    {
        // Валидация
        if (empty($data['track_name'])) {
            throw new \InvalidArgumentException('Название трека обязательно');
        }

        // Если указан poem_id, подтягиваем данные
        if (!empty($data['poem_id'])) {
            $poem = $this->poemRepo->find($data['poem_id']);
            if ($poem) {
                $data['poet_id'] = $poem->poet_id;
                if (empty($data['track_name'])) {
                    $data['track_name'] = $poem->name;
                }
            }
        }

        // Генерация slug
        if (empty($data['track_slug'])) {
            $data['track_slug'] = $this->generateSlug($data['track_name']);
        }

        return $this->trackRepo->create($data);
    }

    /**
     * Записать прослушивание
     */
    public function recordPlay(int $trackId, int $userId = 0, ?int $duration = null): bool
    {
        return $this->interactionRepo->play($trackId, $userId, null, $duration);
    }

    /**
     * Поставить лайк
     */
    public function like(int $trackId, int $userId): bool
    {
        return $this->interactionRepo->like($trackId, $userId);
    }

    /**
     * Убрать лайк
     */
    public function unlike(int $trackId, int $userId): bool
    {
        return $this->interactionRepo->unlike($trackId, $userId);
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
