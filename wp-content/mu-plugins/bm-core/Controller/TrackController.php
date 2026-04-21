<?php
namespace BM\Core\Controller;

use BM\Core\Service\TrackService;
use BM\Core\Service\SearchService;

class TrackController extends BaseController
{
    private TrackService $trackService;
    private SearchService $searchService;

    public function __construct()
    {
        $this->trackService = new TrackService();
        $this->searchService = new SearchService();
    }

    /**
     * GET /api/tracks - список треков
     */
   public function index(): void
{
    $page = (int) ($_GET['page'] ?? 1);
    $limit = (int) ($_GET['limit'] ?? 20);
    
    // Ограничиваем максимальный лимит
    $maxLimit = $this->config['pagination']['max_limit'] ?? 100;
    $limit = min($limit, $maxLimit);
    
    // Фильтры
    $filters = [
        'voice_gender' => $_GET['voice_gender'] ?? null,
        'lang' => $_GET['lang'] ?? null,
        'mood_id' => $_GET['mood_id'] ?? null,
        'theme_id' => $_GET['theme_id'] ?? null,
        'genre_id' => $_GET['genre_id'] ?? null,
        'style_id' => $_GET['style_id'] ?? null,
    ];
    $filters = array_filter($filters);
    
    // Получаем данные
    $result = $this->trackService->getTrackRepo()->getFiltered($filters, $page, $limit);
    
    // Формируем ответ (как в старом методе)
    $response = [
        'success' => true,
        'data' => [
            'items' => $result['data'],
            'page' => $result['pagination']['page'],
            'limit' => $result['pagination']['per_page'],
            'total' => $result['pagination']['total'],
            'pages' => $result['pagination']['last_page'],
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
}

    /**
     * GET /api/tracks/{id} - один трек
     */
    public function show(int $id): string
    {
        $track = $this->trackService->getTrackWithDetails($id);
        
        if (!$track) {
            return $this->jsonError('Трек не найден', 404);
        }

        return $this->jsonSuccess($track);
    }

    /**
     * POST /api/tracks - создать трек
     */
    public function store(): string
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Не авторизован', 401);
        }

        $data = $this->getParams();
        
        try {
            $id = $this->trackService->createTrack($data);
            return $this->jsonSuccess(['id' => $id], 'Трек создан', 201);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/tracks/{id}/play - записать прослушивание
     */
    public function play(int $id): string
    {
        $userId = $this->getCurrentUserId() ?: 0;
        $duration = $this->getParam('duration');
        
        $this->trackService->recordPlay($id, $userId, $duration);
        
        return $this->jsonSuccess(null, 'Прослушивание записано');
    }

    /**
     * POST /api/tracks/{id}/like - поставить лайк
     */
    public function like(int $id): string
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->jsonError('Не авторизован', 401);
        }

        $result = $this->trackService->like($id, $userId);
        
        return $this->jsonSuccess(['liked' => $result]);
    }

    /**
     * DELETE /api/tracks/{id}/like - убрать лайк
     */
    public function unlike(int $id): string
    {
        $userId = $this->getCurrentUserId();
        
        if (!$userId) {
            return $this->jsonError('Не авторизован', 401);
        }

        $result = $this->trackService->unlike($id, $userId);
        
        return $this->jsonSuccess(['unliked' => $result]);
    }

    /**
     * GET /api/tracks/popular - популярные треки
     */
    public function popular(): string
    {
        $limit = (int) ($this->getParam('limit', 10));
        $period = $this->getParam('period', 'week');
        
        $tracks = $this->trackService->trackRepo->getPopular($limit);
        
        return $this->jsonSuccess($tracks);
    }

    /**
     * GET /api/tracks/recent - новые треки
     */
    public function recent(): string
    {
        $limit = (int) ($this->getParam('limit', 10));
        
        $tracks = $this->trackService->trackRepo->getRecent($limit);
        
        return $this->jsonSuccess($tracks);
    }

    /**
     * GET /api/tracks/search - поиск треков
     */
    public function search(): string
    {
        $query = $this->getParam('q', '');
        $limit = (int) ($this->getParam('limit', 20));
        
        if (empty($query)) {
            return $this->jsonError('Пустой поисковый запрос', 400);
        }

        $tracks = $this->searchService->searchTracks($query, $limit);
        
        return $this->jsonSuccess($tracks);
    }
	

}
