<?php
/**
 * WorkConnect PH - Utility Functions
 */

require_once 'config.php';

/**
 * Get all job categories
 * @return array
 */
function getJobCategories() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT category_id, category_name FROM job_category WHERE is_active = TRUE ORDER BY category_name");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Get featured jobs
 * @param int $limit
 * @return array
 */
function getFeaturedJobs($limit = 10) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT j.job_id, j.job_title, j.job_description, j.location, j.salary_min, j.salary_max, 
               j.job_type, j.created_at, e.company_name, c.category_name
        FROM job j
        JOIN employer e ON j.employer_id = e.employer_id
        JOIN job_category c ON j.category_id = c.category_id
        WHERE j.status = 'published' AND j.application_deadline >= CURDATE()
        ORDER BY j.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $jobs = [];
    
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    return $jobs;
}

/**
 * Get jobs posted by a specific employer (NEW FUNCTION)
 * @param int $employer_id
 * @return array
 */
function getEmployerJobs($employer_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT 
            j.job_id, j.job_title, j.location, j.job_type, j.salary_min, j.salary_max, j.status, 
            j.applications_count, j.created_at
        FROM job j
        WHERE j.employer_id = ?
        ORDER BY j.created_at DESC
    ");
    $stmt->bind_param("i", $employer_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $jobs = [];
    
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    return $jobs;
}


/**
 * Search jobs with filters
 * @param array $filters
 * @return array
 */
function searchJobs($filters) {
    $conn = getDBConnection();
    
    $whereConditions = ["j.status = 'published'", "j.application_deadline >= CURDATE()"];
    $params = [];
    $types = "";
    
    // Keyword search
    if (!empty($filters['keyword'])) {
        $whereConditions[] = "(j.job_title LIKE ? OR j.job_description LIKE ? OR e.company_name LIKE ?)";
        $keyword = "%" . $filters['keyword'] . "%";
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "sss";
    }
    
    // Location filter
    if (!empty($filters['location'])) {
        $whereConditions[] = "j.location LIKE ?";
        $params[] = "%" . $filters['location'] . "%";
        $types .= "s";
    }
    
    // Job type filter
    if (!empty($filters['job_type'])) {
        $whereConditions[] = "j.job_type = ?";
        $params[] = $filters['job_type'];
        $types .= "s";
    }
    
    // Category filter
    if (!empty($filters['category'])) {
        $whereConditions[] = "j.category_id = ?";
        $params[] = $filters['category'];
        $types .= "i";
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $sql = "
        SELECT j.job_id, j.job_title, j.job_description, j.location, j.salary_min, j.salary_max, 
               j.job_type, j.created_at, e.company_name, c.category_name,
               (SELECT COUNT(*) FROM application a WHERE a.job_id = j.job_id) as applications_count
        FROM job j
        JOIN employer e ON j.employer_id = e.employer_id
        JOIN job_category c ON j.category_id = c.category_id
        WHERE $whereClause
        ORDER BY j.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    return $jobs;
}

/**
 * Get job details by ID
 * @param int $job_id
 * @return array|null
 */
function getJobDetails($job_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT j.*, e.company_name, e.company_description, e.website_url, c.category_name
        FROM job j
        JOIN employer e ON j.employer_id = e.employer_id
        JOIN job_category c ON j.category_id = c.category_id
        WHERE j.job_id = ?
    ");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}

/**
 * Get job skills
 * @param int $job_id
 * @return array
 */
function getJobSkills($job_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT skill_name, importance_level FROM job_skill WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $skills = [];
    
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row;
    }
    
    return $skills;
}

/**
 * Apply for a job
 * @param int $job_id
 * @param int $job_seeker_id
 * @param array $application_data
 * @return array
 */
function applyForJob($job_id, $job_seeker_id, $application_data) {
    $conn = getDBConnection();
    
    // Check if already applied
    $checkStmt = $conn->prepare("SELECT application_id FROM application WHERE job_id = ? AND job_seeker_id = ?");
    $checkStmt->bind_param("ii", $job_id, $job_seeker_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'You have already applied for this job.'];
    }
    
    // Insert application
    $stmt = $conn->prepare("
        INSERT INTO application (job_id, job_seeker_id, cover_letter, expected_salary, availability_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iisds", 
        $job_id, 
        $job_seeker_id,
        $application_data['cover_letter'],
        $application_data['expected_salary'],
        $application_data['availability_date']
    );
    
    if ($stmt->execute()) {
        // Update applications count
        $updateStmt = $conn->prepare("UPDATE job SET applications_count = applications_count + 1 WHERE job_id = ?");
        $updateStmt->bind_param("i", $job_id);
        $updateStmt->execute();
        
        return ['success' => true, 'message' => 'Application submitted successfully!', 'application_id' => $conn->insert_id];
    } else {
        return ['success' => false, 'message' => 'Failed to submit application. Please try again.'];
    }
}

/**
 * Get user applications
 * @param int $job_seeker_id
 * @return array
 */
function getUserApplications($job_seeker_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT a.*, j.job_title, e.company_name, j.location
        FROM application a
        JOIN job j ON a.job_id = j.job_id
        JOIN employer e ON j.employer_id = e.employer_id
        WHERE a.job_seeker_id = ?
        ORDER BY a.applied_at DESC
    ");
    $stmt->bind_param("i", $job_seeker_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $applications = [];
    
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    
    return $applications;
}

/**
 * Format currency for display
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = 'PHP') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Get user notifications
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function getUserNotifications($user_id, $limit = 10) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM notification 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}
?>
