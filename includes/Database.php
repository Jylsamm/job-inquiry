<?php
/**
 * Database Connection Manager
 * Implements Singleton pattern for database connection management
 */
class Database {
    private static $instance = null;
    private $connection;
    private $queries = [];
    private $queryCount = 0;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $this->connection->set_charset(DB_CHARSET);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Enable strict mode for better data integrity
            $this->connection->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($query) {
        $this->queryCount++;
        $this->queries[] = $query;
        return $this->connection->prepare($query);
    }
    
    public function query($query) {
        $this->queryCount++;
        $this->queries[] = $query;
        return $this->connection->query($query);
    }
    
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    public function commit() {
        $this->connection->commit();
    }
    
    public function rollback() {
        $this->connection->rollback();
    }
    
    public function getQueryCount() {
        return $this->queryCount;
    }
    
    public function getQueries() {
        return $this->queries;
    }
    
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}