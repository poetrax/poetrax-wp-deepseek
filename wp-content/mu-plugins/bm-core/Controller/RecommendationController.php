<?php
namespace BM\Core\Controller;

use BM\Core\Service\Recommendation\RecommendationService;

class RecommendationController extends BaseController
{
    private RecommendationService $recommendationService;
    
    public function __construct()
    {
        $this->recommendationService = new RecommendationService();
    }
    
    /**
     * GET /api/recommendations/user
     */
    public function forUser(): string
    {
        $userId = $this->getCurrentUserId();
        $limit = (int)($this->getParam('limit', 20));
        
        if (!$userId) {
            // Неавторизованным — популярное
            $tracks = $this->recommendationService->getPopular($limit);
        } else {
            $tracks = $this->recommendationService->forUser($userId, $limit);
        }
        
        return $this->jsonSuccess($tracks);
    }
    
    /**
     * GET /api/recommendations/track/{id}
     */
    public function similarToTrack(int $trackId): string
    {
        $limit = (int)($this->getParam('limit', 10));
        $tracks = $this->recommendationService->similarToTrack($trackId, $limit);
        
        return $this->jsonSuccess($tracks);
    }
    
    /**
     * GET /api/recommendations/popular
     */
    public function popular(): string
    {
        $limit = (int)($this->getParam('limit', 20));
        $tracks = $this->recommendationService->getPopular($limit);
        
        return $this->jsonSuccess($tracks);
    }
    
    /**
     * GET /api/recommendations/new
     */
    public function newReleases(): string
    {
        $limit = (int)($this->getParam('limit', 20));
        $tracks = $this->recommendationService->getNewReleases($limit);
        
        return $this->jsonSuccess($tracks);
    }
    
    /**
     * GET /api/recommendations/trending
     */
    public function trending(): string
    {
        $limit = (int)($this->getParam('limit', 20));
        $tracks = $this->recommendationService->getTrending($limit);
        
        return $this->jsonSuccess($tracks);
    }
    
    /**
     * GET /api/recommendations/poet/{id}
     */
    public function forPoet(int $poetId): string
    {
        $limit = (int)($this->getParam('limit', 10));
        $tracks = $this->recommendationService->forPoet($poetId, $limit);
        
        return $this->jsonSuccess($tracks);
    }
    
    /**
     * GET /api/recommendations/poem/{id}
     */
    public function forPoem(int $poemId): string
    {
        $limit = (int)($this->getParam('limit', 10));
        $tracks = $this->recommendationService->forPoem($poemId, $limit);
        
        return $this->jsonSuccess($tracks);
    }
}
