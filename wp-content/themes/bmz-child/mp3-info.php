<?php
require_once(__DIR__ . '/getid3/getid3.php');
class SimpleAudioInfo {
    private $getID3;
    
    public function __construct() {
        $this->getID3 = new getID3();
    }
    
    public function getInfo($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("Файл не найден: " . $filePath);
            }
            
            $info = $this->getID3->analyze($filePath);
            
            return [
                'success' => true,
                'data' => [
                    'artist'    => $this->getTag($info, 'artist'),
                    'title'     => $this->getTag($info, 'title'),
                    'album'     => $this->getTag($info, 'album'),
                    'year'      => $this->getTag($info, 'year'),
                    'genre'     => $this->getTag($info, 'genre'),
                    'duration'  => $info['playtime_string'] ?? '0:00',
                    'bitrate'   => round(($info['audio']['bitrate'] ?? 0) / 1000) . ' kbps',
                    'format'    => $info['fileformat'] ?? 'unknown'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getTag($info, $tagName) {
        // Проверяем разные версии ID3 тегов
        $versions = ['id3v2', 'id3v1', 'ape'];
        
        foreach ($versions as $version) {
            if (isset($info['tags'][$version][$tagName][0])) {
                return $info['tags'][$version][$tagName][0];
            }
        }
        
        return 'Неизвестно';
    }
}

// Использование
/*
$audioInfo = new SimpleAudioInfo();
$result = $audioInfo->getInfo('music/song.mp3');

if ($result['success']) {
    echo "Исполнитель: " . $result['data']['artist'] . "<br>";
    echo "Название: " . $result['data']['title'] . "<br>";
    echo "Длительность: " . $result['data']['duration'] . "<br>";
} else {
    echo "Ошибка: " . $result['error'];
}
*/