<?php
class Db
{
    protected $pdo;
             
    public function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        }
    }

    public static function query($sql, $params = []) {
       try {
            static $sth = null;
            if (!$sth) {
                $sth = $this->pdo->prepare($sql_query);
            }
            foreach ($params as $key => &$value) {
                $sth->bindValue($key, $value);
            }
            $sth->execute();
            return $sth;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), 'utils.php executeQuery');
            return null;
        }
    }
    
    protected function handleDatabaseError(PDOException $e)
    {
        $message = 'PDO error: ' . $e->getMessage();
        $this->logError($message,'db.php handleDatabaseError');
        throw new Exception($message, 500, $e);
    }

    public function logError($errorMessage, $event)
    {
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] ERROR: $errorMessage $event" . PHP_EOL;
        error_log($logMessage, 3, LOG_FILE);
    }

    public function __destruct()
    {
        if ($this->pdo != null) { $this->pdo = null; }
    }
    
    public function getPDO()
    {
        return $this->pdo;
    }
}