<?php
/**
 * Custom Exception Handler
 */
class AppException extends Exception {
    protected $context;
    protected $severity;
    
    public function __construct($message, $code = 0, $severity = 'error', $context = [], Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->severity = $severity;
    }
    
    public function getContext() {
        return $this->context;
    }
    
    public function getSeverity() {
        return $this->severity;
    }
}

class ErrorHandler {
    private static $instance = null;
    private $logPath;
    
    private function __construct() {
        $this->logPath = __DIR__ . '/../api/logs/';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function handleException($e) {
        $timestamp = date('Y-m-d H:i:s');
        $severity = ($e instanceof AppException) ? $e->getSeverity() : 'error';
        $context = ($e instanceof AppException) ? $e->getContext() : [];
        
        // Log the error
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\nContext: %s\n",
            $timestamp,
            $severity,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
            json_encode($context)
        );
        
        $logFile = $this->logPath . date('Y-m-d') . '_error.log';
        error_log($logMessage, 3, $logFile);
        
        // Return appropriate response based on the environment
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            $response = [
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
                'code' => $e->getCode() ?: 500
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'context' => $context,
                'code' => $e->getCode() ?: 500
            ];
        }
        
        http_response_code($response['code']);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    public function handleError($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    public function register() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        
        // Handle fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
                $this->handleException(
                    new ErrorException(
                        $error['message'], 
                        0, 
                        $error['type'], 
                        $error['file'], 
                        $error['line']
                    )
                );
            }
        });
    }
}