<?php
/**
 * WorkConnect PH - Profiles API (Job Seeker & Employer)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Require authentication for all profile operations (using the fixed function from previous step)
requireAuth(); 

$method = $_SERVER['REQUEST_METHOD'];
$role = getCurrentUserRole();
$user_id = getCurrentUserId();

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($role, $user_id);
            break;
            
        case 'POST':
            handlePostRequest($role, $user_id);
            break;
            
        case 'PUT':
            handlePutRequest($role, $user_id);
            break;
            
        default:
            jsonResponse(false, 'Method not allowed.', null, 405);
    }
} catch (Exception $e) {
    error_log("Profiles API Error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred while processing your request.', null, 500);
}

/**
 * Handle GET requests
 */
function handleGetRequest($role, $user_id) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'job_seeker_profile':
            if ($role !== 'job_seeker') {
                jsonResponse(false, 'Access denied. Job seeker role required.', null, 403);
            }
            $profile = getJobSeekerProfile($user_id);
            if ($profile) {
                jsonResponse(true, 'Job seeker profile retrieved.', $profile);
            } else {
                jsonResponse(false, 'Job seeker profile not found or requires setup.');
            }
            break;
            
        case 'employer_profile':
            if ($role !== 'employer') {
                jsonResponse(false, 'Access denied. Employer role required.', null, 403);
            }
            $profile = getEmployerProfile($user_id);
            if ($profile) {
                jsonResponse(true, 'Employer profile retrieved.', $profile);
            } else {
                jsonResponse(false, 'Employer profile not found or requires setup.');
            }
            break;
            
        case 'user_stats':
            $stats = getUserStatistics($user_id, $role);
            jsonResponse(true, 'User statistics retrieved.', $stats);
            break;
            
        case 'recent_activities':
            $limit = $_GET['limit'] ?? 5;
            $activities = getRecentActivities($user_id, $role, $limit);
            jsonResponse(true, 'Recent activities retrieved.', $activities);
            break;
            
        default:
            jsonResponse(false, 'Invalid action specified.');
    }
}

/**
 * Handle POST requests (mainly for file uploads)
 */
function handlePostRequest($role, $user_id) {
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    // CSRF Token Validation
    if (!isset($input['csrf_token']) || !validateCsrfToken($input['csrf_token'])) {
        jsonResponse(false, 'Invalid security token. Please refresh the page.', null, 403);
    }
    
    switch ($action) {
        case 'upload_profile_picture':
            // Handle profile picture upload via separate endpoint
            // This would typically handle multipart/form-data
            jsonResponse(false, 'Use dedicated upload endpoint for file uploads.');
            break;
            
        case 'upload_company_logo':
            // Handle company logo upload via separate endpoint
            jsonResponse(false, 'Use dedicated upload endpoint for file uploads.');
            break;
            
        default:
            jsonResponse(false, 'Invalid action for POST request.');
    }
}

/**
 * Handle PUT requests (for profile updates)
 */
function handlePutRequest($role, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    // CSRF Token Validation
    if (!isset($input['csrf_token']) || !validateCsrfToken($input['csrf_token'])) {
        jsonResponse(false, 'Invalid security token. Please refresh the page.', null, 403);
    }
    
    // Input validation
    if (empty($input)) {
        jsonResponse(false, 'No data provided for update.');
    }
    
    switch ($action) {
        case 'save_job_seeker':
            if ($role !== 'job_seeker') {
                jsonResponse(false, 'Access denied. Job seeker role required.', null, 403);
            }
            
            // Validate required fields
            $required = ['first_name', 'last_name', 'email'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    jsonResponse(false, "Required field missing: " . str_replace('_', ' ', $field));
                }
            }
            
            // Email validation
            $emailValidation = validateEmailUnique($input['email'], $user_id);
            if (!$emailValidation['valid']) {
                jsonResponse(false, $emailValidation['message']);
            }
            
            $result = saveJobSeekerProfile($user_id, $input);
            jsonResponse($result['success'], $result['message'], $result['data'] ?? null);
            break;
            
        case 'save_employer':
            if ($role !== 'employer') {
                jsonResponse(false, 'Access denied. Employer role required.', null, 403);
            }
            
            // Validate required fields
            $required = ['first_name', 'last_name', 'email', 'company_name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    jsonResponse(false, "Required field missing: " . str_replace('_', ' ', $field));
                }
            }
            
            // Email validation
            $emailValidation = validateEmailUnique($input['email'], $user_id);
            if (!$emailValidation['valid']) {
                jsonResponse(false, $emailValidation['message']);
            }
            
            $result = saveEmployerProfile($user_id, $input);
            jsonResponse($result['success'], $result['message'], $result['data'] ?? null);
            break;
            
        default:
            jsonResponse(false, 'Invalid action specified.');
    }
}

/**
 * Enhanced JSON response helper with HTTP status codes
 */
function jsonResponse($success, $message = '', $data = null, $httpCode = 200) {
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
    }
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}
?>