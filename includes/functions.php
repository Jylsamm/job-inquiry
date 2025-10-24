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
        $stmt = db_prepare_or_error($conn, "SELECT category_id, category_name FROM job_category WHERE is_active = TRUE ORDER BY category_name");
        $stmt->execute();
    $result = $stmt->get_result();
    
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
        $stmt = db_prepare_or_error($conn, "
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
    
    // Keyword search - use prepared statements
    if (!empty($filters['keyword'])) {
        $whereConditions[] = "(j.job_title LIKE ? OR j.job_description LIKE ? OR e.company_name LIKE ?)";
        $keyword = "%" . $conn->real_escape_string($filters['keyword']) . "%";
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $types .= "sss";
    }
    
    // Location filter
    if (!empty($filters['location'])) {
        $whereConditions[] = "j.location LIKE ?";
        $params[] = "%" . $conn->real_escape_string($filters['location']) . "%";
        $types .= "s";
    }
    
    // Job type filter
    if (!empty($filters['job_type'])) {
        $whereConditions[] = "j.job_type = ?";
        $params[] = $conn->real_escape_string($filters['job_type']);
        $types .= "s";
    }
    
    // Category filter
    if (!empty($filters['category'])) {
        $whereConditions[] = "j.category_id = ?";
        $params[] = (int)$filters['category'];
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
    
        $stmt = db_prepare_or_error($conn, $sql);
    
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
        $checkStmt = db_prepare_or_error($conn, "SELECT application_id FROM application WHERE job_id = ? AND job_seeker_id = ?");
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

// =============================================
// NEW: Profile Management Functions
// =============================================

/**
 * Get job seeker profile details
 * @param int $user_id
 * @return array|null
 */
function getJobSeekerProfile($user_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
     SELECT u.email, u.first_name, u.last_name, u.phone, u.profile_picture,
               js.headline, js.bio, js.location, js.expected_salary, js.experience_level
     FROM user u
        JOIN job_seeker js ON u.user_id = js.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Save job seeker profile details
 * @param int $user_id
 * @param array $data
 * @return array
 */
function saveJobSeekerProfile($user_id, $data) {
    $conn = getDBConnection();
    
    // 1. Update User table
    $user_stmt = $conn->prepare("
        UPDATE user SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?
    ");
    $user_stmt->bind_param("ssssi", 
        $data['first_name'], 
        $data['last_name'], 
        $data['email'], 
        $data['phone'], 
        $user_id
    );
    $user_stmt->execute();
    
    // 2. Update Job Seeker table
    $js_stmt = $conn->prepare("
        UPDATE job_seeker SET headline = ?, bio = ?, location = ?, expected_salary = ? 
        WHERE user_id = ?
    ");
    $js_stmt->bind_param("sssdi", 
        $data['headline'], 
        $data['bio'], 
        $data['location'], 
        $data['expected_salary'], 
        $user_id
    );
    
    if ($js_stmt->execute()) {
        return ['success' => true, 'message' => 'Job Seeker profile updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to update job seeker profile.'];
    }
}

/**
 * Get employer profile details
 * @param int $user_id
 * @return array|null
 */
function getEmployerProfile($user_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
     SELECT u.email, u.first_name, u.last_name, u.phone, u.profile_picture,
               e.company_name, e.company_description, e.industry, e.website_url, e.company_logo, e.company_size
     FROM user u
        JOIN employer e ON u.user_id = e.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Save employer profile details
 * @param int $user_id
 * @param array $data
 * @return array
 */
function saveEmployerProfile($user_id, $data) {
    $conn = getDBConnection();
    
    // 1. Update User table (for contact person details)
    $user_stmt = $conn->prepare("
        UPDATE user SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?
    ");
    $user_stmt->bind_param("ssssi", 
        $data['first_name'], 
        $data['last_name'], 
        $data['email'], 
        $data['phone'], 
        $user_id
    );
    $user_stmt->execute();
    
    // 2. Update Employer table
    $emp_stmt = $conn->prepare("
        UPDATE employer SET company_name = ?, company_description = ?, industry = ?, website_url = ? 
        WHERE user_id = ?
    ");
    $emp_stmt->bind_param("ssssi", 
        $data['company_name'], 
        $data['company_description'], 
        $data['industry'], 
        $data['website_url'], 
        $user_id
    );
    
    if ($emp_stmt->execute()) {
        return ['success' => true, 'message' => 'Employer profile updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to update employer profile.'];
    }
}

// =============================================
// NEW: File Upload and Validation Functions
// =============================================

/**
 * Upload profile picture with validation
 * @param array $file
 * @param int $user_id
 * @return array
 */
function uploadProfilePicture($file, $user_id) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed with error code: ' . $file['error']];
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return ['success' => false, 'message' => 'File size must be less than 2MB.'];
    }
    
    // Validate file type using finfo
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF allowed.'];
    }
    
    // Validate file extension
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file extension.'];
    }
    
    // Check image dimensions
    $image_info = getimagesize($file['tmp_name']);
    if (!$image_info) {
        return ['success' => false, 'message' => 'Invalid image file.'];
    }
    
    // Generate secure filename
    $filename = 'profile_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $upload_path = UPLOAD_PATH . 'profiles/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $full_path = $upload_path . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        // Update database with relative path
        $relative_path = '/job-inquiry/uploads/profiles/' . $filename;
        $conn = getDBConnection();
            $stmt = db_prepare_or_error($conn, "UPDATE user SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $relative_path, $user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Profile picture updated.', 'file_path' => $relative_path];
        } else {
            // Delete the uploaded file if database update fails
            unlink($full_path);
            return ['success' => false, 'message' => 'Failed to update database.'];
        }
    } else {
        return ['success' => false, 'message' => 'Failed to save file.'];
    }
}

/**
 * Upload company logo with validation
 * @param array $file
 * @param int $employer_id
 * @return array
 */
function uploadCompanyLogo($file, $employer_id) {
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed.'];
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return ['success' => false, 'message' => 'File size must be less than 2MB.'];
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'company_' . $employer_id . '_' . time() . '.' . $extension;
    $upload_path = UPLOAD_PATH . 'companies/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $full_path = $upload_path . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        // Update database with relative path
        $relative_path = '/job-inquiry/uploads/companies/' . $filename;
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE employer SET company_logo = ? WHERE employer_id = ?");
        $stmt->bind_param("si", $relative_path, $employer_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Company logo updated.', 'file_path' => $relative_path];
        } else {
            return ['success' => false, 'message' => 'Failed to update database.'];
        }
    } else {
        return ['success' => false, 'message' => 'Failed to save file.'];
    }
}

/**
 * Validate email format and uniqueness
 * @param string $email
 * @param int $current_user_id
 * @return array
 */
function validateEmailUnique($email, $current_user_id = null) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Please enter a valid email address.'];
    }
    
    $conn = getDBConnection();
    
    if ($current_user_id) {
        // Check if email exists for other users (for profile updates)
        $stmt = $conn->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $current_user_id);
    } else {
        // Check if email exists (for registration)
        $stmt = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['valid' => false, 'message' => 'This email address is already registered.'];
    }
    
    return ['valid' => true, 'message' => 'Email is valid.'];
}

/**
 * Get user statistics for dashboard
 * @param int $user_id
 * @param string $user_role
 * @return array
 */
function getUserStatistics($user_id, $user_role) {
    $conn = getDBConnection();
    $stats = [];
    
    if ($user_role === 'job_seeker') {
        // Job seeker stats
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM application WHERE job_seeker_id = ?) as total_applications,
                (SELECT COUNT(*) FROM application WHERE job_seeker_id = ? AND status = 'pending') as pending_applications,
                (SELECT COUNT(*) FROM application WHERE job_seeker_id = ? AND status = 'accepted') as accepted_applications
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
    } elseif ($user_role === 'employer') {
        // Employer stats
        $employer_id = getCurrentEmployerId();
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM job WHERE employer_id = ?) as total_jobs,
                (SELECT COUNT(*) FROM job WHERE employer_id = ? AND status = 'published') as active_jobs,
                (SELECT COUNT(*) FROM application a 
                 JOIN job j ON a.job_id = j.job_id 
                 WHERE j.employer_id = ?) as total_applications
        ");
        $stmt->bind_param("iii", $employer_id, $employer_id, $employer_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
    }
    
    return $stats;
}

/**
 * Get recent activities for user
 * @param int $user_id
 * @param string $user_role
 * @param int $limit
 * @return array
 */
function getRecentActivities($user_id, $user_role, $limit = 5) {
    $conn = getDBConnection();
    $activities = [];
    
    if ($user_role === 'job_seeker') {
        $stmt = $conn->prepare("
            SELECT 
                a.applied_at as activity_date,
                CONCAT('Applied for ', j.job_title) as activity_description,
                'application' as activity_type
            FROM application a
            JOIN job j ON a.job_id = j.job_id
            WHERE a.job_seeker_id = ?
            ORDER BY a.applied_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        
    } elseif ($user_role === 'employer') {
        $employer_id = getCurrentEmployerId();
        $stmt = $conn->prepare("
            SELECT 
                j.created_at as activity_date,
                CONCAT('Posted job: ', j.job_title) as activity_description,
                'job_post' as activity_type
            FROM job j
            WHERE j.employer_id = ?
            UNION
            SELECT 
                a.applied_at as activity_date,
                CONCAT('New application for ', j.job_title) as activity_description,
                'new_application' as activity_type
            FROM application a
            JOIN job j ON a.job_id = j.job_id
            WHERE j.employer_id = ?
            ORDER BY activity_date DESC
            LIMIT ?
        ");
        $stmt->bind_param("iii", $employer_id, $employer_id, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    return $activities;
}
?>