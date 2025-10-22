job-inquiry/api/upload.php:
<?php
/**
 * WorkConnect PH - File Upload API
 * Separate endpoint for handling file uploads (profile pictures, company logos)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Require authentication
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$role = getCurrentUserRole();
$user_id = getCurrentUserId();

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        jsonResponse(false, 'Invalid security token. Please refresh the page.', null, 403);
    }
    
    switch ($action) {
        case 'upload_profile_picture':
            if ($role !== 'job_seeker' && $role !== 'employer') {
                jsonResponse(false, 'Access denied.', null, 403);
            }
            
            if (!isset($_FILES['profile_picture'])) {
                jsonResponse(false, 'No file uploaded.');
            }
            
            $result = uploadProfilePicture($_FILES['profile_picture'], $user_id);
            jsonResponse($result['success'], $result['message'], $result['file_path'] ?? null);
            break;
            
        case 'upload_company_logo':
            if ($role !== 'employer') {
                jsonResponse(false, 'Access denied. Employer role required.', null, 403);
            }
            
            $employer_id = getCurrentEmployerId();
            if (!$employer_id) {
                jsonResponse(false, 'Employer profile not found.');
            }
            
            if (!isset($_FILES['company_logo'])) {
                jsonResponse(false, 'No file uploaded.');
            }
            
            $result = uploadCompanyLogo($_FILES['company_logo'], $employer_id);
            jsonResponse($result['success'], $result['message'], $result['file_path'] ?? null);
            break;
            
        default:
            jsonResponse(false, 'Invalid upload action.');
    }
} else {
    jsonResponse(false, 'Method not allowed.', null, 405);
}