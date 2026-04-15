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
    public function index(): string
    {
        $page = (int) ($this->getParam('page', 1));
        $limit = (int) ($this->getParam('limit', 20));
        
        $tracks = $this->trackService->getTrackRepo()->getPaginated($page, $limit);
        $total = $this->trackService->getTrackRepo()->count();

        return $this->jsonSuccess([
            'items' => $tracks,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]);
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
