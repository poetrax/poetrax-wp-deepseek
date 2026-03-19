<?php
namespace BM\Core\Controller;

use BM\Core\Service\Filter\FilterService;

class FilterController extends BaseController
{
    private FilterService $filterService;
    
    public function __construct()
    {
        $this->filterService = new FilterService();
    }
    
    /**
     * POST /api/filter/{entity}
     */
    public function filter(string $entity): string
    {
        $filters = $this->getParams();
        $limit = (int)($this->getParam('limit', 20));
        $page = (int)($this->getParam('page', 1));
        $offset = ($page - 1) * $limit;
        
        $method = 'filter' . ucfirst($entity);
        if (!method_exists($this->filterService, $method)) {
            return $this->jsonError("Unknown entity: $entity", 400);
        }
        
        try {
            $items = $this->filterService->$method($filters, $limit, $offset);
            
            return $this->jsonSuccess([
                'items' => $items,
                'page' => $page,
                'limit' => $limit,
                'total' => count($items),
                'filters_applied' => array_keys(array_filter($filters))
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/filter/{entity}/available
     */
    public function availableFilters(string $entity): string
    {
        $filters = $this->filterService->getAvailableFilters($entity);
        return $this->jsonSuccess($filters);
    }
	
	  /**
     * POST /api/filter/poets/by-track-properties
     */
    public function filterPoetsByTrackProperties(): string
    {
        $filters = $this->getParams();
        $limit = (int)($this->getParam('limit', 20));
        $page = (int)($this->getParam('page', 1));
        $offset = ($page - 1) * $limit;
        
        try {
            $items = $this->filterService->filterPoetsByTrackProperties($filters, $limit, $offset);
            
            return $this->jsonSuccess([
                'items' => $items,
                'page' => $page,
                'limit' => $limit,
                'total' => count($items)
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * POST /api/filter/poems/by-track-properties
     */
    public function filterPoemsByTrackProperties(): string
    {
        $filters = $this->getParams();
        $limit = (int)($this->getParam('limit', 20));
        $page = (int)($this->getParam('page', 1));
        $offset = ($page - 1) * $limit;
        
        try {
            $items = $this->filterService->filterPoemsByTrackProperties($filters, $limit, $offset);
            
            return $this->jsonSuccess([
                'items' => $items,
                'page' => $page,
                'limit' => $limit,
                'total' => count($items)
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }
    
    /**
     * POST /api/filter/users/by-track-properties
     */
    public function filterUsersByTrackProperties(): string
    {
        $filters = $this->getParams();
        $limit = (int)($this->getParam('limit', 20));
        $page = (int)($this->getParam('page', 1));
        $offset = ($page - 1) * $limit;
        
        try {
            $items = $this->filterService->filterUsersByTrackProperties($filters, $limit, $offset);
            
            return $this->jsonSuccess([
                'items' => $items,
                'page' => $page,
                'limit' => $limit,
                'total' => count($items)
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }
}
