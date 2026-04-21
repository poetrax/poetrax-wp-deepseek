<?php
namespace BM\Core\Service;

use BM\Core\Repository\ServiceRepository;
use BM\Core\Repository\UserServiceAccessRepository;

class AccessService
{
    private ServiceRepository $serviceRepo;
    private UserServiceAccessRepository $accessRepo;

    public function __construct()
    {
        $this->serviceRepo = new ServiceRepository();
        $this->accessRepo = new UserServiceAccessRepository();
    }

    /**
     * Проверить, имеет ли пользователь доступ к услуге
     */
    public function hasAccess(int $userId, string $serviceSlug): bool
    {
        $service = $this->serviceRepo->getBySlug($serviceSlug);
        if (!$service) {
            return false;
        }
        
        return $this->accessRepo->hasAccess($userId, $service['id']);
    }

    /**
     * Получить все доступы пользователя
     */
    public function getUserAccesses(int $userId): array
    {
        return $this->accessRepo->getUserAccesses($userId);
    }

    /**
     * Купить доступ к услуге
     */
    public function purchaseAccess(int $userId, string $serviceSlug, string $paymentMethod = 'points'): array
    {
        $service = $this->serviceRepo->getBySlug($serviceSlug);
        if (!$service) {
            throw new \Exception('Service not found');
        }
        
        // Проверка, есть ли уже доступ
        if ($this->hasAccess($userId, $serviceSlug)) {
            return ['success' => true, 'message' => 'Access already exists', 'service' => $service];
        }
        
        // Списание баллов или оплата
        if ($paymentMethod === 'points') {
            $this->deductPoints($userId, $service['price_points']);
        }
        
        // Создание доступа
        $durationDays = $service['is_subscription'] ? $service['duration_days'] : null;
        $this->accessRepo->createAccess($userId, $service['id'], 'full', $durationDays);
        
        return [
            'success' => true,
            'message' => 'Access granted',
            'service' => $service,
            'duration_days' => $durationDays
        ];
    }

    /**
     * Списать баллы
     */
    private function deductPoints(int $userId, int $points): void
    {
        // Здесь логика списания баллов из баланса пользователя
        // $balanceRepo->deduct($userId, $points);
    }

    /**
     * Проверить доступ к эндпоинту (для middleware)
     */
    public function checkEndpointAccess(int $userId, string $endpoint): bool
    {
        $serviceMap = [
            '/api/tracks' => 'listen',
            '/api/tracks/download' => 'download',
            '/api/orders' => 'order_track',
            '/api/messages' => 'messages',
            '/api/comments' => 'comments'
        ];
        
        $serviceSlug = $serviceMap[$endpoint] ?? null;
        if (!$serviceSlug) {
            return true; // Публичный эндпоинт
        }
        
        return $this->hasAccess($userId, $serviceSlug);
    }
}