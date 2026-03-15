<?php
namespace BM\Repositories;

class RepositoryFactory {
    
    /**
     * Получить репозиторий для типа сущности
     */
    public static function get($entity_type) {
        switch ($entity_type) {
            case 'track':
                return new TrackRepository();
            case 'poem':
                return new PoemRepository();
            case 'poet':
                return new PoetRepository();
            case 'image':
                return new ImageRepository();
            case 'doc':
                return new DocRepository();
            default:
                throw new \InvalidArgumentException("Unknown entity type: {$entity_type}");
        }
    }
    
    /**
     * Получить все репозитории
     */
    public static function getAll() {
        return [
            'track' => new TrackRepository(),
            'poem' => new PoemRepository(),
            'poet' => new PoetRepository(),
            'image' => new ImageRepository(),
            'doc' => new DocRepository(),
        ];
    }
    
    /**
     * Получить название таблицы для типа
     */
    public static function getTableName($entity_type) {
        $tables = [
            'track' => 'bm_ctbl000_track',
            'poem' => 'bm_ctbl000_poem',
            'poet' => 'bm_ctbl000_poet',
            'image' => 'bm_ctbl000_img',
            'doc' => 'bm_ctbl000_docs',
        ];
        
        return $tables[$entity_type] ?? null;
    }
}

/*
Пример использования
<?php
// Создание нового трека
$track_repo = new BM\Repositories\TrackRepository();
$track_id = $track_repo->create([
    'user_id' => 1,
    'track_name' => 'Мой новый трек',
    'poet_id' => 1,
    'poem_id' => 1,
    'performance_type' => 'song',
    'voice_gender' => 'male',
    'track_duration' => 180,
]);

// Обновление
$track_repo->update($track_id, [
    'track_name' => 'Новое название',
    'is_approved' => 1,
]);

// Удаление
$track_repo->delete($track_id);

// Использование фабрики
$repo = BM\Repositories\RepositoryFactory::get('poem');
$poems = $repo->getAll(10);
*/

