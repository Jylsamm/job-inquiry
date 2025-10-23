<?php
/**
 * Security middleware for API endpoints
 */

class ApiMiddleware {
    private $security;
    private $db;
    
    public function __construct() {
        $this->security = Security::getInstance();
        $this->db = Database::getInstance();
    }
    
    public function handle() {
        $this->setSecurityHeaders();
        $this->checkRateLimit();
        $this->validateToken();
    }
    
    private function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';');
        
        if (ENVIRONMENT === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    private function checkRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $endpoint = $_SERVER['REQUEST_URI'];
        
        $stmt = $this->db->prepare("
            INSERT INTO rate_limits (ip_address, endpoint, requests, window_start)
            VALUES (?, ?, 1, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                requests = IF(
                    window_start < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE),
                    1,
                    requests + 1
                ),
                window_start = IF(
                    window_start < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE),
                    CURRENT_TIMESTAMP,
                    window_start
                )
        ");
        
        $stmt->bind_param('ss', $ip, $endpoint);
        $stmt->execute();
        
        $stmt = $this->db->prepare("
            SELECT requests, window_start
            FROM rate_limits
            WHERE ip_address = ? AND endpoint = ?
        ");
        
        $stmt->bind_param('ss', $ip, $endpoint);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['requests'] > API_RATE_LIMIT) {
            http_response_code(429);
            header('Retry-After: 60');
            die(json_encode([
                'status' => 'error',
                'message' => 'Rate limit exceeded. Please try again in 1 minute.'
            ]));
        }
    }
    
    private function validateToken() {
        // Skip token validation for public endpoints
        $publicEndpoints = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/jobs/search',
            '/api/jobs/categories'
        ];
        
        if (in_array($_SERVER['REQUEST_URI'], $publicEndpoints)) {
            return;
        }
        
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $auth = explode(' ', $headers['Authorization']);
            if (count($auth) === 2 && strtolower($auth[0]) === 'bearer') {
                $token = $auth[1];
            }
        }
        
        if (!$token) {
            http_response_code(401);
            die(json_encode([
                'status' => 'error',
                'message' => 'No authentication token provided'
            ]));
        }
        
        try {
            $payload = $this->security->verifyJwt($token);
            $_SESSION['user_id'] = $payload->user_id;
            $_SESSION['user_type'] = $payload->user_type;
        } catch (Exception $e) {
            http_response_code(401);
            die(json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ]));
        }
    }
}