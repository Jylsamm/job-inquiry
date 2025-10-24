-- =============================================
-- WorkConnect PH - Database Optimizations
-- Date: 2025-10-24
-- Description: Performance and integrity improvements
-- =============================================

-- 1. Add Soft Delete Support
ALTER TABLE user ADD COLUMN deleted_at DATETIME DEFAULT NULL;
ALTER TABLE job ADD COLUMN deleted_at DATETIME DEFAULT NULL;
ALTER TABLE application ADD COLUMN deleted_at DATETIME DEFAULT NULL;
ALTER TABLE employer ADD COLUMN deleted_at DATETIME DEFAULT NULL;
ALTER TABLE job_seeker ADD COLUMN deleted_at DATETIME DEFAULT NULL;

ALTER TABLE user ADD INDEX idx_deleted_at (deleted_at);
ALTER TABLE job ADD INDEX idx_deleted_at (deleted_at);
ALTER TABLE application ADD INDEX idx_deleted_at (deleted_at);
ALTER TABLE employer ADD INDEX idx_deleted_at (deleted_at);
ALTER TABLE job_seeker ADD INDEX idx_deleted_at (deleted_at);

-- 2. Add Composite Indexes for Common Queries
ALTER TABLE job 
ADD INDEX idx_job_search (status, application_deadline, job_type, location),
ADD INDEX idx_salary_range (salary_min, salary_max),
ADD INDEX idx_category_date (category_id, created_at),
ADD FULLTEXT INDEX ft_job_search (job_title, job_description, requirements);

ALTER TABLE application 
ADD INDEX idx_application_search (job_seeker_id, status, applied_at);

ALTER TABLE job_seeker
ADD INDEX idx_seeker_search (experience_level, education_level, location),
ADD FULLTEXT INDEX ft_seeker_search (headline, bio, location);

ALTER TABLE employer 
ADD FULLTEXT INDEX ft_employer_search (company_name, company_description, industry);

ALTER TABLE user 
ADD INDEX idx_auth (email, password_hash, is_active);

-- 3. Add Data Integrity Constraints
ALTER TABLE job 
ADD CONSTRAINT chk_salary_range 
CHECK (salary_max >= salary_min);

ALTER TABLE education 
ADD CONSTRAINT chk_education_dates 
CHECK (end_date IS NULL OR end_date >= start_date);

ALTER TABLE work_experience 
ADD CONSTRAINT chk_work_dates 
CHECK (end_date IS NULL OR end_date >= start_date);

-- 4. Add Performance Monitoring Tables
CREATE TABLE query_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    query_text TEXT,
    execution_time DECIMAL(10,4),
    rows_affected INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    
    INDEX idx_timestamp (timestamp),
    INDEX idx_execution_time (execution_time)
) ENGINE=InnoDB;

CREATE TABLE schema_migrations (
    version VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_table (table_name),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    requests INT UNSIGNED DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB;

-- 5. Add Automated Triggers and Events
DELIMITER //

-- Update application counts
CREATE TRIGGER after_application_insert
AFTER INSERT ON application
FOR EACH ROW
BEGIN
    UPDATE job 
    SET applications_count = applications_count + 1 
    WHERE job_id = NEW.job_id;
END;
//

-- Track job views
CREATE TRIGGER after_job_view_insert
AFTER INSERT ON job_view
FOR EACH ROW
BEGIN
    UPDATE job 
    SET views_count = views_count + 1 
    WHERE job_id = NEW.job_id;
END;
//

-- Auto-update job status based on deadline
CREATE EVENT update_expired_jobs
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE job 
    SET status = 'expired' 
    WHERE application_deadline < CURDATE() 
    AND status = 'published'
    AND deleted_at IS NULL;
END;
//

DELIMITER ;