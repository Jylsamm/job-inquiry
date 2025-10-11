<?php
/**
 * WorkConnect PH - Jobs API
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check authentication for certain actions
if (!isLoggedIn() && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    jsonResponse(false, 'Authentication required.');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'featured':
                $jobs = getFeaturedJobs(10);
                jsonResponse(true, 'Featured jobs retrieved', $jobs);
                break;
                
            case 'search':
                $filters = [
                    'keyword' => $_GET['q'] ?? '',
                    'location' => $_GET['location'] ?? '',
                    'job_type' => $_GET['type'] ?? '',
                    'category' => $_GET['category'] ?? ''
                ];
                
                $jobs = searchJobs($filters);
                jsonResponse(true, 'Search results', $jobs);
                break;
                
            case 'details':
                $job_id = $_GET['id'] ?? 0;
                if ($job_id) {
                    $job = getJobDetails($job_id);
                    if ($job) {
                        $job['skills'] = getJobSkills($job_id);
                        jsonResponse(true, 'Job details retrieved', $job);
                    } else {
                        jsonResponse(false, 'Job not found.');
                    }
                } else {
                    jsonResponse(false, 'Job ID required.');
                }
                break;
                
            case 'categories':
                $categories = getJobCategories();
                jsonResponse(true, 'Categories retrieved', $categories);
                break;
                
            case 'employer_posted': // NEW ACTION to get jobs posted by the current employer
                if (hasRole('employer')) {
                    $employer_id = getCurrentEmployerId();
                    if (!$employer_id) {
                        jsonResponse(false, 'Employer profile not found.');
                    }
                    $jobs = getEmployerJobs($employer_id);
                    jsonResponse(true, 'Posted jobs retrieved', $jobs);
                } else {
                    jsonResponse(false, 'Access denied.');
                }
                break;
                
            default:
                jsonResponse(false, 'Invalid action.');
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'apply':
                if (hasRole('job_seeker')) {
                    // FIX: Use getCurrentJobSeekerId() instead of user_id
                    $job_seeker_id = getCurrentJobSeekerId();
                    if (!$job_seeker_id) {
                        jsonResponse(false, 'Job seeker profile not found.');
                    }
                    $result = applyForJob($input['job_id'], $job_seeker_id, $input);
                    jsonResponse($result['success'], $result['message'], $result['application_id'] ?? null);
                } else {
                    jsonResponse(false, 'Only job seekers can apply for jobs.');
                }
                break;
                
            case 'save':
                // Save job for later
                if (hasRole('job_seeker')) {
                    $conn = getDBConnection();
                    $job_seeker_id = getCurrentJobSeekerId();
                    
                    if (!$job_seeker_id) {
                        jsonResponse(false, 'Job seeker profile not found.');
                    }
                    
                    $stmt = $conn->prepare("INSERT IGNORE INTO job_saved (job_seeker_id, job_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $job_seeker_id, $input['job_id']);
                    
                    if ($stmt->execute()) {
                        jsonResponse(true, 'Job saved successfully!');
                    } else {
                        jsonResponse(false, 'Failed to save job or already saved.');
                    }
                } else {
                    jsonResponse(false, 'Authentication required.');
                }
                break;
                
            case 'post_job': // NEW ACTION for job creation
                if (hasRole('employer')) {
                    $employer_id = getCurrentEmployerId();
                    if (!$employer_id) {
                        jsonResponse(false, 'Employer profile not found.');
                    }
                    
                    // Input validation and sanitization (simplified for example)
                    $required_fields = ['job_title', 'job_description', 'requirements', 'salary_min', 'salary_max', 'job_type', 'experience_level', 'location'];
                    foreach ($required_fields as $field) {
                        if (empty($input[$field])) {
                            jsonResponse(false, "Missing required field: " . $field);
                        }
                    }
                    
                    // Simulate using a default category ID since the form doesn't provide one
                    $default_category_id = 1; 

                    $conn = getDBConnection();
                    $stmt = $conn->prepare("
                        INSERT INTO job (
                            employer_id, category_id, job_title, job_description, requirements, 
                            salary_min, salary_max, job_type, experience_level, location, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
                    ");
                    
                    $stmt->bind_param("iissssssss", 
                        $employer_id, 
                        $default_category_id, 
                        $input['job_title'], 
                        $input['job_description'], 
                        $input['requirements'], 
                        $input['salary_min'], 
                        $input['salary_max'], 
                        $input['job_type'], 
                        $input['experience_level'],
                        $input['location']
                    );
                    
                    if ($stmt->execute()) {
                        jsonResponse(true, 'Job posted successfully and set to published!', ['job_id' => $conn->insert_id]);
                    } else {
                        jsonResponse(false, 'Failed to post job.');
                    }
                } else {
                    jsonResponse(false, 'Access denied. Must be an employer.');
                }
                break;
                
            default:
                jsonResponse(false, 'Invalid action.');
        }
        break;
        
    default:
        jsonResponse(false, 'Method not allowed.');
}
?>
