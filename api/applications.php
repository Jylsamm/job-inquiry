<?php
/**
 * WorkConnect PH - Applications API
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    jsonResponse(false, 'Authentication required.');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'my_applications':
                if (hasRole('job_seeker')) {
                    // FIX: Use getCurrentJobSeekerId() instead of user_id
                    $job_seeker_id = getCurrentJobSeekerId();
                    if (!$job_seeker_id) {
                        jsonResponse(false, 'Job seeker profile not found.');
                    }
                    $applications = getUserApplications($job_seeker_id);
                    jsonResponse(true, 'Applications retrieved', $applications);
                } else {
                    jsonResponse(false, 'Access denied.');
                }
                break;
                
            case 'employer_applications':
                if (hasRole('employer')) {
                    // FIX: Use getCurrentEmployerId() instead of user_id
                    $employer_id = getCurrentEmployerId();
                    if (!$employer_id) {
                        jsonResponse(false, 'Employer profile not found.');
                    }
                    
                    $conn = getDBConnection();
                    
                    $stmt = $conn->prepare("
                        SELECT a.*, j.job_title, js.user_id, u.first_name, u.last_name, u.email
                        FROM application a
                        JOIN job j ON a.job_id = j.job_id
                        JOIN job_seeker js ON a.job_seeker_id = js.job_seeker_id
                        JOIN user u ON js.user_id = u.user_id
                        WHERE j.employer_id = ?
                        ORDER BY a.applied_at DESC
                    ");
                    $stmt->bind_param("i", $employer_id);
                    $stmt->execute();
                    if ($stmt === false) {
                        jsonResponse(false, 'Internal server error (DB prepare failed)', null, 500);
                    }
                    
                    $result = $stmt->get_result();
                    $applications = [];
                    
                    while ($row = $result->fetch_assoc()) {
                        $applications[] = $row;
                    }
                    
                    jsonResponse(true, 'Applications retrieved', $applications);
                } else {
                    jsonResponse(false, 'Access denied.');
                }
                break;
                
            default:
                jsonResponse(false, 'Invalid action.');
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $application_id = $_GET['id'] ?? 0;
        
        if ($application_id && hasRole('employer')) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE application SET status = ?, status_notes = ? WHERE application_id = ?");
            $stmt->bind_param("ssi", $input['status'], $input['notes'], $application_id);
            if ($stmt === false) {
                jsonResponse(false, 'Internal server error (DB prepare failed)', null, 500);
            }
            
            if ($stmt->execute()) {
                // Log status change
                $historyStmt = $conn->prepare("INSERT INTO application_status_history (application_id, old_status, new_status, change_notes, changed_by_user_id) VALUES (?, ?, ?, ?, ?)");
                $historyStmt->bind_param("isssi", $application_id, $input['old_status'], $input['status'], $input['notes'], $_SESSION['user_id']);
                $historyStmt->execute();
                if ($historyStmt === false) {
                    jsonResponse(false, 'Internal server error (DB prepare failed)', null, 500);
                }
                
                jsonResponse(true, 'Application status updated.');
            } else {
                jsonResponse(false, 'Failed to update application.');
            }
        } else {
            jsonResponse(false, 'Invalid request.');
        }
        break;
        
    default:
        jsonResponse(false, 'Method not allowed.');
}
?>