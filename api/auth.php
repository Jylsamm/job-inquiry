<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Validator.php';
require_once __DIR__ . '/../includes/ApiResponse.php';
require_once __DIR__ . '/../includes/Logger.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$logger = Logger::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
if (!$input && $method === 'POST') {
    $input = $_POST;
}

// Debug: log incoming request for troubleshooting
@file_put_contents(__DIR__ . '/logs/auth_debug.log', date('c') . ' ' . json_encode([
    'method' => $method,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    'query_string' => $_SERVER['QUERY_STRING'] ?? null,
    'get_action' => $_GET['action'] ?? null,
    'input' => $input
]) . PHP_EOL, FILE_APPEND);

// Also write a short debug line into the main log file so we can see it with existing logs
error_log(date('c') . " AUTH_DEBUG: request_uri=" . ($_SERVER['REQUEST_URI'] ?? '-') . " query=" . ($_SERVER['QUERY_STRING'] ?? '-') . " get_action=" . ($_GET['action'] ?? '-') . PHP_EOL, 3, __DIR__ . '/logs/2025-10-24.log');

try {
    $action = $_GET['action'] ?? $input['action'] ?? '';
    if (empty($action)) {
        throw new Exception('Action parameter is required');
    }

    // DEBUG: return the resolved action directly for troubleshooting
    // Remove this debug block after investigation
    ApiResponse::success(['debug_action' => $action]);

    switch ($action) {
        case 'check':
            $data = ['logged_in' => isLoggedIn(), 'role' => getCurrentUserRole()];
            ApiResponse::success(['data' => $data]);
            break;

        case 'login':
            $result = loginUser($input['email'] ?? '', $input['password'] ?? '');
            if ($result['success']) {
                ApiResponse::success(['data' => ['role' => $result['role'] ?? null], 'message' => $result['message'] ?? 'Login successful']);
            } else {
                ApiResponse::error($result['message'] ?? 'Login failed', 401);
            }
            break;

        case 'register':
            $result = registerUser($input ?? []);
            if ($result['success']) {
                ApiResponse::success(['data' => ['user_id' => $result['user_id'] ?? null], 'message' => $result['message'] ?? 'Registered']);
            } else {
                ApiResponse::error($result['message'] ?? 'Registration failed', 400);
            }
            break;

        case 'logout':
            $result = logoutUser();
            ApiResponse::success(['message' => $result['message'] ?? 'Logged out']);
            break;

        case 'forgot_password':
        case 'forgot-password':
            $result = initiatePasswordReset($input['email'] ?? '');
            ApiResponse::success(['data' => $result]);
            break;

        case 'reset_password':
        case 'reset-password':
            $result = completePasswordReset($input['token'] ?? '', $input['new_password'] ?? '');
            if ($result['success']) {
                ApiResponse::success(['message' => $result['message']]);
            } else {
                ApiResponse::error($result['message'] ?? 'Reset failed', 400);
            }
            break;

        case 'verify_email':
        case 'verify-email':
            $result = verifyEmail($input['token'] ?? '');
            if ($result['success']) {
                ApiResponse::success(['message' => $result['message']]);
            } else {
                ApiResponse::error($result['message'] ?? 'Verification failed', 400);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $logger->error('Auth API error', ['error' => $e->getMessage()]);
    ApiResponse::error($e->getMessage(), 400);
}
