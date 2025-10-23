<?php
class Security {
    private static $instance = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public function validateCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || 
            empty($_SESSION['csrf_token_time']) || 
            empty($token)) {
            return false;
        }
        
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function rateLimit($key, $maxAttempts = 5, $decayMinutes = 60) {
        $attempts = $_SESSION[$key.'_attempts'] ?? 0;
        $lastAttemptTime = $_SESSION[$key.'_last_attempt'] ?? 0;
        
        if (time() - $lastAttemptTime > ($decayMinutes * 60)) {
            $attempts = 0;
        }
        
        if ($attempts >= $maxAttempts) {
            throw new Exception('Too many attempts. Please try again later.');
        }
        
        $_SESSION[$key.'_attempts'] = $attempts + 1;
        $_SESSION[$key.'_last_attempt'] = time();
    }
}