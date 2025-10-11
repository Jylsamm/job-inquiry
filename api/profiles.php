<?php
/**
 * WorkConnect PH - Profiles API (Job Seeker & Employer)
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Require authentication for all profile operations
if (!isLoggedIn()) {
    jsonResponse(false, 'Authentication required.');
}

$method = $_SERVER['REQUEST_METHOD'];
$role = getCurrentUserRole();
$user_id = getCurrentUserId();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        if ($role === 'job_seeker' && $action === 'job_seeker_profile') {
            $profile = getJobSeekerProfile($user_id);
            if ($profile) {
                jsonResponse(true, 'Job seeker profile retrieved.', $profile);
            } else {
                jsonResponse(false, 'Job seeker profile not found.');
            }
        } elseif ($role === 'employer' && $action === 'employer_profile') {
            $profile = getEmployerProfile($user_id);
            if ($profile) {
                jsonResponse(true, 'Employer profile retrieved.', $profile);
            } else {
                jsonResponse(false, 'Employer profile not found.');
            }
        } else {
            jsonResponse(false, 'Invalid action or role access.');
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        if ($role === 'job_seeker' && $action === 'save_job_seeker') {
            $result = saveJobSeekerProfile($user_id, $input);
            jsonResponse($result['success'], $result['message']);
        } elseif ($role === 'employer' && $action === 'save_employer') {
            $result = saveEmployerProfile($user_id, $input);
            jsonResponse($result['success'], $result['message']);
        } else {
            jsonResponse(false, 'Invalid action or role access.');
        }
        break;
        
    default:
        jsonResponse(false, 'Method not allowed.');
}
?>
