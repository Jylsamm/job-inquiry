<?php
/**
 * Database Connection Manager
 * Implements Singleton pattern for database connection management with enhanced error tracking
 */
class Database {
    private static $instance = null;
    private $connection;
    private $queries = [];
    private $queryCount = 0;
    private $logger;
    private $inTransaction = false;
    
    private function __construct() {
        $this->logger = Logger::getInstance();
        
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $this->connection->set_charset(DB_CHARSET);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Enable strict mode for better data integrity
            $this->connection->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            
            // Set wait_timeout and interactive_timeout
            $this->connection->query("SET SESSION wait_timeout=300"); // 5 minutes
            $this->connection->query("SET SESSION interactive_timeout=300");
            
            $this->logger->info("Database connection established successfully");
        } catch (Exception $e) {
            $this->logger->error("Database connection error: " . $e->getMessage());
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
        // Ensure connection is alive; try to reconnect if needed
        if (!$this->connection) {
            $this->logger->warning('Database connection missing, attempting reconnect');
            $this->__construct();
        } else {
            // ping returns false if connection dropped
            if (!$this->connection->ping()) {
                $this->logger->warning('Lost DB connection, reconnecting');
                try {
                    $this->connection->close();
                } catch (Exception $e) {
                    // ignore
                }
                $this->__construct();
            }
        }

        return $this->connection;
    }
    
    public function prepare($query) {
        $this->queryCount++;
        $this->queries[] = $query;

        $stmt = $this->connection->prepare($query);
        if ($stmt === false) {
            $this->logger->error('Failed to prepare statement: ' . $this->connection->error, ['query' => $query]);
            return false;
        }

        return $stmt;
    }

    /**
     * Prepare statement or throw exception (useful for APIs)
     */
    public function prepareOrFail($query) {
        $stmt = $this->prepare($query);
        if ($stmt === false) {
            $msg = 'Database prepare failed: ' . $this->connection->error;
            $this->logger->error($msg, ['query' => $query]);
            throw new Exception($msg);
        }
        return $stmt;
    }
    
    public function query($query) {
        $this->queryCount++;
        $this->queries[] = $query;

        $start = microtime(true);
        $result = $this->connection->query($query);
        $duration = microtime(true) - $start;

        if ($duration > 1.0) {
            $this->logger->warning("Slow query ({$duration}s): " . $query);
            // Attempt to insert into query_log table if it exists
            try {
                if ($this->connection->query("SHOW TABLES LIKE 'query_log'")->num_rows === 1) {
                    $rowsAffected = 0;
                    if ($result instanceof mysqli_result) {
                        $rowsAffected = $result->num_rows;
                    } else {
                        $rowsAffected = $this->connection->affected_rows;
                    }

                    $safeStmt = $this->connection->prepare(
                        "INSERT INTO query_log (query_text, execution_time, rows_affected, user_id) VALUES (?, ?, ?, ?)"
                    );
                    if ($safeStmt) {
                        $userId = $_SESSION['user_id'] ?? null;
                        $safeStmt->bind_param('sdii', $query, $duration, $rowsAffected, $userId);
                        $safeStmt->execute();
                    }
                }
            } catch (Exception $e) {
                // Don't break application for logging errors
                $this->logger->error('Failed to write query_log: ' . $e->getMessage());
            }
        }

        if ($result === false) {
            $this->logger->error('Query failed: ' . $this->connection->error, ['query' => $query]);
        }

        return $result;
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