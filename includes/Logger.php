<?php
class Logger {
    private static $instance = null;
    private $logPath;
    private $maxLogSize = 10485760; // 10MB
    private $maxLogFiles = 30; // Keep last 30 days of logs
    
    private function __construct() {
        $this->logPath = __DIR__ . '/../api/logs/';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        // Rotate logs if needed
        $this->rotateLogsIfNeeded();
        
        // Clean old logs
        $this->cleanOldLogs();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function debug($message, $context = []) {
        if (defined('DEBUG') && DEBUG === true) {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = $this->logPath . date('Y-m-d') . '.log';
        
        $contextStr = empty($context) ? '' : json_encode($context);
        $logMessage = "[{$timestamp}] {$level}: {$message} {$contextStr}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    public function getLogPath() {
        return $this->logPath;
    }
    
    public function clearLogs($daysOld = 30) {
        $files = glob($this->logPath . '*.log');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $daysOld) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Rotate current log file if it exceeds max size
     */
    private function rotateLogsIfNeeded() {
        $todayLog = $this->logPath . date('Y-m-d') . '.log';
        if (!file_exists($todayLog)) return;

        clearstatcache(true, $todayLog);
        $size = filesize($todayLog);
        if ($size !== false && $size >= $this->maxLogSize) {
            $rotated = $this->logPath . date('Y-m-d_His') . '.log';
            @rename($todayLog, $rotated);
        }
    }

    /**
     * Remove old logs beyond retention window
     */
    private function cleanOldLogs() {
        $files = glob($this->logPath . '*.log');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                // Default retention is $this->maxLogFiles days
                $daysOld = $this->maxLogFiles;
                if ($now - filemtime($file) >= 60 * 60 * 24 * $daysOld) {
                    @unlink($file);
                }
            }
        }
    }
}