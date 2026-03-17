<?php
namespace BM\Core\Service;

use BM\Core\Repository\UserRepository;
use BM\Core\Repository\TrackRepository;
use BM\Core\Repository\InteractionRepository;

class UserService
{
    private UserRepository $userRepo;
    private TrackRepository $trackRepo;
    private InteractionRepository $interactionRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->trackRepo = new TrackRepository();
        $this->interactionRepo = new InteractionRepository();
    }

    /**
     * Получить пользователя с полной информацией
     */
    public function getUserWithDetails(int $userId): ?object
    {
        $user = $this->userRepo->find($userId);
        
        if (!$user) {
            return null;
        }

        // Статистика
        $user->stats = (object)[
            'tracks_count' => $this->getUserTracksCount($userId),
            'likes_count' => $this->getUserLikesCount($userId),
            'bookmarks_count' => $this->getUserBookmarksCount($userId),
            'plays_count' => $this->getUserPlaysCount($userId)
        ];

        // Полное имя
        $user->full_name = $this->userRepo->getFullName($user);

        return $user;
    }

    /**
     * Регистрация нового пользователя
     */
    public function register(array $data): int
    {
        // Валидация
        $this->validateRegistrationData($data);

        // Проверка уникальности
        if ($this->userRepo->exists($data['user_login'], $data['user_email'])) {
            throw new \RuntimeException('Пользователь с таким логином или email уже существует');
        }

        // Хеширование пароля
        if (isset($data['user_pass'])) {
            $data['password_hash'] = password_hash($data['user_pass'], PASSWORD_DEFAULT);
            unset($data['user_pass']);
        }

        // Значения по умолчанию
        $data['user_registered'] = date('Y-m-d H:i:s');
        $data['user_status'] = $data['user_status'] ?? 0;

        return $this->userRepo->create($data);
    }

    /**
     * Авторизация пользователя
     */
    public function authenticate(string $login, string $password): ?object
    {
        $user = $this->userRepo->findByLoginOrEmail($login);
        
        if (!$user) {
            return null;
        }

        // Проверка пароля
        if (!password_verify($password, $user->password_hash)) {
            return null;
        }

        // Проверка необходимости рехэша
        if (password_needs_rehash($user->password_hash, PASSWORD_DEFAULT)) {
            $this->updatePasswordHash($user->id, $password);
        }

        // Обновляем время последнего визита
        $this->userRepo->updateLastVisit($user->id);

        return $user;
    }

    /**
     * Смена пароля
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepo->find($userId);
        
        if (!$user) {
            throw new \RuntimeException('Пользователь не найден');
        }

        // Проверка текущего пароля
        if (!password_verify($currentPassword, $user->password_hash)) {
            throw new \RuntimeException('Неверный текущий пароль');
        }

        // Проверка сложности нового пароля
        $this->validatePasswordStrength($newPassword);

        // Обновление пароля
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return (bool) $this->userRepo->update($userId, [
            'password_hash' => $newHash
        ]);
    }

    /**
     * Получить треки пользователя
     */
    public function getUserTracks(int $userId, int $limit = 20): array
    {
        return $this->trackRepo->findBy(['user_id' => $userId], $limit, 'created_at DESC');
    }

    /**
     * Получить понравившиеся треки пользователя
     */
    public function getUserLikedTracks(int $userId, int $limit = 20): array
    {
        $trackIds = $this->interactionRepo->getUserTrackIds($userId, 'like', $limit);
        
        if (empty($trackIds)) {
            return [];
        }

        // Получаем полные объекты треков
        $tracks = [];
        foreach ($trackIds as $trackId) {
            $track = $this->trackRepo->find($trackId);
            if ($track) {
                $tracks[] = $track;
            }
        }

        return $tracks;
    }

    /**
     * Получить закладки пользователя
     */
    public function getUserBookmarkedTracks(int $userId, int $limit = 20): array
    {
        $trackIds = $this->interactionRepo->getUserTrackIds($userId, 'bookmark', $limit);
        
        if (empty($trackIds)) {
            return [];
        }

        $tracks = [];
        foreach ($trackIds as $trackId) {
            $track = $this->trackRepo->find($trackId);
            if ($track) {
                $tracks[] = $track;
            }
        }

        return $tracks;
    }

    /**
     * Получить рекомендации для пользователя
     */
    public function getRecommendationsForUser(int $userId, int $limit = 20): array
    {
        // Простая логика: берём популярные треки
        // Позже можно усложнить на основе предпочтений пользователя
        return $this->trackRepo->getPopular($limit);
    }

    /**
     * Вспомогательные методы
     */
    private function validateRegistrationData(array $data): void
    {
        if (empty($data['user_login'])) {
            throw new \InvalidArgumentException('Логин обязателен');
        }

        if (strlen($data['user_login']) < 3) {
            throw new \InvalidArgumentException('Логин должен быть не менее 3 символов');
        }

        if (empty($data['user_email'])) {
            throw new \InvalidArgumentException('Email обязателен');
        }

        if (!filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Неверный формат email');
        }

        if (!empty($data['user_pass'])) {
            $this->validatePasswordStrength($data['user_pass']);
        }
    }

    private function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Пароль должен быть не менее 8 символов');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Пароль должен содержать заглавные буквы');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Пароль должен содержать строчные буквы');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Пароль должен содержать цифры');
        }
    }

    private function updatePasswordHash(int $userId, string $password): void
    {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $this->userRepo->update($userId, ['password_hash' => $newHash]);
    }

    private function getUserTracksCount(int $userId): int
    {
        return $this->trackRepo->count(['user_id' => $userId]);
    }

    private function getUserLikesCount(int $userId): int
    {
        $trackIds = $this->interactionRepo->getUserTrackIds($userId, 'like', 1000);
        return count($trackIds);
    }

    private function getUserBookmarksCount(int $userId): int
    {
        $trackIds = $this->interactionRepo->getUserTrackIds($userId, 'bookmark', 1000);
        return count($trackIds);
    }

    private function getUserPlaysCount(int $userId): int
    {
        // Можно добавить подсчёт прослушиваний через отдельный метод
        return 0;
    }
}
