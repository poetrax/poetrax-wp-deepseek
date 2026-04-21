<?php
namespace BM\Core\Controller;

use BM\Core\Service\PoemService;

class PoemController extends BaseController
{
    private PoemService $poemService;

    public function __construct()
    {
        $this->poemService = new PoemService();
    }

    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $result = $this->poemService->getAll($page, $limit);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $result['items'],
            'pagination' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'pages' => $result['pages']
            ]
        ]);
    }

    public function show(int $id): void
    {
        $poem = $this->poemService->find($id);
        
        if (!$poem) {
            $this->jsonError('Poem not found', 404);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'data' => $poem]);
    }

    public function search(): void
    {
        $query = $_GET['q'] ?? '';
        $result = $this->poemService->search($query);
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    public function byPoet(int $poetId): void
    {
        $poems = $this->poemService->findByPoet($poetId);
        $this->jsonResponse(['success' => true, 'data' => $poems]);
    }

    public function text(int $id): void
    {
        $text = $this->poemService->getText($id);
        
        if (!$text) {
            $this->jsonError('Poem text not found', 404);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'data' => ['text' => $text]]);
    }
}