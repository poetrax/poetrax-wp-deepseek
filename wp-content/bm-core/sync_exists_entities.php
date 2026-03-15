<?php
function bm_sync_existing_entities() {
    $repos = [
        'track' => new BM\Repositories\TrackRepository(),
        'poem'  => new BM\Repositories\PoemRepository(),
        'poet'  => new BM\Repositories\PoetRepository(),
        'image' => new BM\Repositories\ImageRepository(),
        'doc'   => new BM\Repositories\DocRepository(),
    ];
    
    foreach ($repos as $type => $repo) {
        // Получаем все ID этого типа
        $ids = Connection::query("SELECT id FROM " . Connection::table($type));
        
        foreach ($ids as $row) {
            BM\Taxonomies\EntityRelations::setEntityType($row->id, $type);
        }
    }
}

// Запустите один раз
add_action('init', 'bm_sync_existing_entities');
// После выполнения закомментируйте или удалите