<?php
namespace BM\Core\Controller;

use BM\Core\Repository\ProductRepository;

class ProductController extends BaseController
{
    private ProductRepository $productRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
    }

    /**
     * GET /api/products
     * Список товаров
     */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 20);
        
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'category_slug' => $_GET['category'] ?? null,
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
            'search' => $_GET['search'] ?? null,
            'sort' => $_GET['sort'] ?? 'created_at',
            'order' => $_GET['order'] ?? 'DESC'
        ];
        
        $filters = array_filter($filters);
        
        $result = $this->productRepo->getAll($filters, $page, $limit);
        
        // Добавляем изображения для каждого товара
        foreach ($result['items'] as &$product) {
            $product['images'] = $this->productRepo->getImages($product['id']);
            $product['variants'] = $this->productRepo->getVariants($product['id']);
        }
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/products/{id}
     * Детальная информация о товаре
     */
    public function show(int $id): void
    {
        $product = $this->productRepo->getById($id);
        
        if (!$product) {
            $this->jsonError('Product not found', 404);
            return;
        }
        
        $product['images'] = $this->productRepo->getImages($id);
        $product['variants'] = $this->productRepo->getVariants($id);
        
        $this->jsonResponse(['success' => true, 'data' => $product]);
    }

    /**
     * GET /api/products/slug/{slug}
     * Детальная информация по slug
     */
    public function showBySlug(string $slug): void
    {
        $product = $this->productRepo->getBySlug($slug);
        
        if (!$product) {
            $this->jsonError('Product not found', 404);
            return;
        }
        
        $product['images'] = $this->productRepo->getImages($product['id']);
        $product['variants'] = $this->productRepo->getVariants($product['id']);
        
        $this->jsonResponse(['success' => true, 'data' => $product]);
    }
}