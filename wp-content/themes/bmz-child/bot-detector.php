<?php
class BotDetector {
    private $userAgent;
    private $ip;
    
    public function __construct() {
        $this->userAgent = strtolower($_SERVER['HTTP_USER_AGENT']) ?? '';
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    public function isBot(): bool {
        return $this->checkUserAgent() || 
               $this->checkCommonBotPatterns() || 
               $this->checkBehavior() ||
               $this->checkHeaders();
    }

    public function verifySearchEngineBot(): bool {
        $searchBots = [
        //'',
        'apis-google',
        'bingbot',
        'exabot',
        'googlebot-image',
        'googlebot-news',
        'googlebot-video',
        'linkedinbot',
        'mediapartners-google',
        'msnbot',
        'msnbot-media',
        'searchbot',
        'snoopy',
        'stackrambler',
        'statsbot',
        'twitterbot',
        'whatsapp',
        'yahoo!',
        'yahoofeedseeker',
        'yandexaccessibilitybot',
        'yandexadnet',
        'yandexantivirus',
        'yandexblogs',
        'yandexbot',
        'yandexcalendar',
        'yandexcatalog',
        'yandexdirect',
        'yandexdirectdyn',
        'yandexfavicons',
        'yandexfordomain',
        'yandeximageresizer',
        'yandeximages',
        'yandexmarket',
        'yandexmedia',
        'yandexmedianabot',
        'yandexmetrika',
        'yandexmobilebot',
        'yandexnews',
        'yandexnewslinks',
        'yandexontodb',
        'yandexontodbapi',
        'yandexpagechecker',
        'yandexscreenshotbot',
        'yandexsearchshop',
        'yandexsitelinks',
        'yandexspravbot',
        'yandexturbo',
        'yandexverticals',
        'yandexvertis',
        'yandexvideo',
        'yandexvideoparser',
        'yandexwebmaster',
        'applebot',
        'facebookexternalhit',
        'googlebot',
        'mail.ru_bot',
        'telegrambot',
        'yadirectfetcher'
        ];
        
        $ua = strtolower($this->userAgent);

        // Пустой User-Agent
        //if (empty($ua)) return true;

        foreach ($searchBots as $bot) {
            if (strpos($ua, $bot) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function checkUserAgent(): bool {
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'search', 'fetcher',
            'scanner', 'monitor', 'python', 'java', 'curl', 'wget',
            'php', 'ruby', 'go-http', 'node', 'apache', 'nmap',
            'zgrab', 'masscan', 'nessus', 'nikto', 'sqlmap'
        ];
        
        $ua = $this->userAgent;
        
        // Пустой User-Agent
        //if (empty($ua)) return true;
        
        // Проверка паттернов
        foreach ($botPatterns as $pattern) {
            if (strpos($ua, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function checkCommonBotPatterns(): bool {
        // Проверка известных ботов по User-Agent
        $knownBots = [
        //'',
        'accoona',
        'acoonbot',
        'adsbot-google',
        'adsbot-google-mobile',
        'adsbot-google-mobile-apps',
        'ahrefsbot',
        'apis-google',
        'ask jeeves',
        'baiduspider',
        'bingbot',
        'c-t bot',
        'dcpbot',
        'discordbot',
        'domainvader',
        'duckduckbot',
        'exabot',
        'ezooms',
        'findlinks',
        'googlebot-image',
        'googlebot-news',
        'googlebot-video',
        'heritrix',
        'ia_archiver',
        'linkedinbot',
        'mediapartners-google',
        'mj12bot',
        'msnbot',
        'msnbot-media',
        'obot',
        'omniexplorer_bot',
        'openindexspider',
        'paperlibot',
        'proximic',
        'searchbot',
        'seznambot',
        'sistrix',
        'sitestatus',
        'slackbot',
        'snoopy',
        'sogou',
        'spider',
        'spider',
        'stackrambler',
        'statdom.ru',
        'statsbot',
        'tourlentabot',
        'twitterbot',
        'updownerbot',
        'w3c_validator',
        'webalta',
        'whatsapp',
        'yadirectfetcher',
        'yahoo!',
        'yahoofeedseeker',
        'yandexaccessibilitybot',
        'yandexadnet',
        'yandexantivirus',
        'yandexblogs',
        'yandexbot',
        'yandexcalendar',
        'yandexcatalog',
        'yandexdirect',
        'yandexdirectdyn',
        'yandexfavicons',
        'yandexfordomain',
        'yandeximageresizer',
        'yandeximages',
        'yandexmarket',
        'yandexmedia',
        'yandexmedianabot',
        'yandexmetrika',
        'yandexmobilebot',
        'yandexnews',
        'yandexnewslinks',
        'yandexontodb',
        'yandexontodbapi',
        'yandexpagechecker',
        'yandexscreenshotbot',
        'yandexsearchshop',
        'yandexsitelinks',
        'yandexspravbot',
        'yandexturbo',
        'yandexverticals',
        'yandexvertis',
        'yandexvideo',
        'yandexvideoparser',
        'yandexwebmaster',
        'yeti',
        'applebot',
        'domainvader',
        'ezooms',
        'facebookexternalhit',
        'googlebot',
        'mail.ru_bot',
        'nigma.ru',
        'obot',
        'omniexplorer_bot',
        'proximic',
        'statdom.ru',
        'telegrambot'
        ];
        
        $ua = strtolower($this->userAgent);
        
        foreach ($knownBots as $bot) {
            if (strpos($ua, $bot) !== false) {
                return true;
            }
        }
        return false;
    }
 
    private function checkHeaders(): bool {
        // Проверка подозрительных заголовков
        $suspiciousHeaders = [
            'HTTP_ACCEPT' => ['*/*', ''],
            'HTTP_ACCEPT_LANGUAGE' => [''],
            'HTTP_ACCEPT_ENCODING' => [''],
            'HTTP_CONNECTION' => ['close']
        ];
        
        foreach ($suspiciousHeaders as $header => $suspiciousValues) {
            if (isset($_SERVER[$header])) {
                $value = $_SERVER[$header];
                if (in_array($value, $suspiciousValues)) {
                    return true;
                }
            }
        }
        
        // Отсутствие обычных заголовков
        if (!isset($_SERVER['HTTP_ACCEPT']) || !isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return true;
        }
        
        return false;
    }
    
    private function checkBehavior(): bool {
        // Проверка скорости запросов (нужно хранить в сессии/БД)
        $key = 'request_count_' . $this->ip;
        $currentTime = time();
        
        // Пример простой проверки частоты запросов
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'first_request' => $currentTime
            ];
        } else {
            $_SESSION[$key]['count']++;
            
            // Если более 10 запросов в секунду - вероятно бот
            if ($_SESSION[$key]['count'] > 10 && 
                ($currentTime - $_SESSION[$key]['first_request']) <= 1) {
                return true;
            }
            
            // Сброс счетчика каждую минуту
            if (($currentTime - $_SESSION[$key]['first_request']) > 60) {
                $_SESSION[$key] = [
                    'count' => 1,
                    'first_request' => $currentTime
                ];
            }
        }
        return false;
    }
}