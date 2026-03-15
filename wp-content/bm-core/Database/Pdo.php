<?php
namespace BM\Database;

class Pdo
{
   
    private static $tables = [];
    private static $pdo = null;  
    
    public function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];
          
            self::$pdo = new \PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (\PDOException $e) {
            self::handleDatabaseError($e);
        }
    }

     public static function init() {
         global $pdo;
         self::$pdo = $pdo;
       
     self::$tables = [
            'track' => 'bm_ctbl000_track',
            'user' => 'bm_ctbl000_user',
            'poem' => 'bm_ctbl000_poem',
            'poet' => 'bm_ctbl000_poet',
            'doc' => 'bm_ctbl000_docs',
            'img' => 'bm_ctbl000_img',
            'img_theme' => 'bm_ctbl000_img_theme',
            'interaction' => 'bm_ctbl000_interaction',
            'mood' => 'bm_ctbl000_mood',
            'music_direction' => 'bm_ctbl000_music_direction',
            'music_genre' => 'bm_ctbl000_music_genre',
            'music_instrument' => 'bm_ctbl000_music_instrument',
            'music_presentation' => 'bm_ctbl000_music_presentation',
            'music_style' => 'bm_ctbl000_music_style',
            'music_style_full' => 'bm_ctbl000_music_style_full',
            'music_suno_style' => 'bm_ctbl000_music_suno_style',
            'music_temp' => 'bm_ctbl000_music_temp',
            'music_voice_gender' => 'bm_ctbl000_music_voice_gender',
            'music_voice_group' => 'bm_ctbl000_music_voice_group',
            'payment' => 'bm_ctbl000_payment',
            'properties_cache' => 'bm_ctbl000_properties_cache',
            'theme' => 'bm_ctbl000_theme',
            'track_music_detail' => 'bm_ctbl000_track_music_detail',
            'track_self_text' => 'bm_ctbl000_track_self_text',
            'user_account' => 'bm_ctbl000_user_account',
            'user_session' => 'bm_ctbl000_user_session',
            'voice_character' => 'bm_ctbl000_voice_character',
            'voice_register' => 'bm_ctbl000_voice_register',
            'comments' => 'bm_ctbl000_comments',
        ];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
     
    // Получить объект для запросов
    public static function get_db() {
        return self::$pdo;
    }
      
    protected static function handleDatabaseError(\PDOException $e)
    {
        $message = 'PDO error: ' . $e->getMessage();
        self::$logError($message,'db.php handleDatabaseError');
        throw new \Exception($message, 500, $e);
    }

    public static function logError($errorMessage, $event)
    {
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] ERROR: $errorMessage $event" . PHP_EOL;
        error_log($logMessage, 3, LOG_FILE);
    }

    public function __destruct()
    {
        if (self::$pdo != null) { self::$pdo = null; }
    }
    


     /**
     * Get PDO instance (for advanced operations)
     */
    public static function getPDO() {
        self::checkConnection();
        return self::$pdo;
    }


    public static function lastInsertId() {
        $db = self::getInstance();
        return $db->Connection()->lastInsertId();
    }

    /**
     * Set tables configuration
     */
    public static function setTables($tables) {
        self::$tables = $tables;
    }
    
    /**
     * Get table name by key
     */
    public static function table($key) {
        if (!isset(self::$tables[$key])) {
            throw new \Exception("Table {$key} not defined");
        }
        return self::$tables[$key];
    }


        
    /**
     * Execute query and return results
    */
    public static function query($sql, $params = []) {
        self::checkConnection();
        
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (\PDOException $e) {
            self::$logError($e->getMessage(), 'Pdo.php query');
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get single row
     */
    public static function row($sql, $params = []) {
        self::checkConnection();
        
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            throw new \Exception("Row fetch failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get single value
     */
    public static function var($sql, $params = []) {
        self::checkConnection();
        
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new \Exception("Var fetch failed: " . $e->getMessage());
        }
    }
    
    /**
     * Insert data
     */
    public static function insert($table, $data) {
        self::checkConnection();
        
        $table = self::table($table);
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($data);
            return self::$pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("Insert failed: " . $e->getMessage());
        }
    }
    
    /**
     * Update data
     */
    public static function update($table, $data, $where) {
        self::checkConnection();
        
        $table = self::table($table);
        
        // Build SET part
        $set = implode(', ', array_map(function($field) {
            return "{$field} = :{$field}";
        }, array_keys($data)));
        
        // Build WHERE part
        $whereClause = implode(' AND ', array_map(function($field) {
            return "{$field} = :where_{$field}";
        }, array_keys($where)));
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$whereClause}";
        
        // Merge parameters with where prefix
        $params = [];
        foreach ($data as $key => $value) {
            $params[$key] = $value;
        }
        foreach ($where as $key => $value) {
            $params["where_{$key}"] = $value;
        }
        
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception("Update failed: " . $e->getMessage());
        }
    }
    
    /**
     * Delete data
     */
    public static function delete($table, $where) {
        self::checkConnection();
        
        $table = self::table($table);
        
        $whereClause = implode(' AND ', array_map(function($field) {
            return "{$field} = :{$field}";
        }, array_keys($where)));
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($where);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception("Delete failed: " . $e->getMessage());
        }
    }
    
    /**
     * Escape value (PDO uses prepared statements, but this is for compatibility)
     */
    public static function escape($value) {
        self::checkConnection();
        return substr(self::$pdo->quote($value), 1, -1);
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction() {
        self::checkConnection();
        return self::$pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit() {
        self::checkConnection();
        return self::$pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollBack() {
        self::checkConnection();
        return self::$pdo->rollBack();
    }
    
    /**
     * Check if PDO connection exists
     */
    private static function checkConnection() {
        if (!self::$pdo) {
           error_log("No connection, try connect.");
          try{
           self::get_db();
          }
          catch(e) {
             throw new \Exception("Database connection not initialized. Call Database::init() first.");
          }
        }
    }

}

/*
Примеры использования
// Инициализация подключения (вместо глобального $wpdb)
BM\Database\Db::init('localhost', 'database_name', 'username', 'password');

// Настройка таблиц
BM\Database\Db::setTables([
    'users' => 'wp_users',
    'posts' => 'wp_posts',
    'options' => 'wp_options'
]);

// Использование методов
try {
    // SELECT query
    $results = BM\Database\Db::query("SELECT * FROM " . BM\Database\Db::table('users') . " WHERE user_email = ?", ['test@example.com']);
    
    // Get single row
    $user = BM\Database\Db::row("SELECT * FROM " . BM\Database\Db::table('users') . " WHERE ID = ?", [1]);
    
    // Get single value
    $count = BM\Database\Db::var("SELECT COUNT(*) FROM " . BM\Database\Db::table('posts'));
    
    // Insert
    $newId = BM\Database\Db::insert('users', [
        'user_login' => 'newuser',
        'user_email' => 'new@example.com'
    ]);
    
    // Update
    $affected = BM\Database\Db::update('users', 
        ['display_name' => 'Updated Name'], 
        ['ID' => $newId]
    );
    
    // Delete
    $deleted = BM\Database\Db::delete('users', ['ID' => $newId]);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

*/