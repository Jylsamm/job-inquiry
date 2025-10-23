-- Migration: Add optimizations and soft deletes
-- Filename: database/migrations/2025_10_24_add_optimizations.sql

-- Add composite indexes for job search
ALTER TABLE job 
ADD INDEX idx_job_search (status, application_deadline, job_title),
ADD INDEX idx_job_filters (location, job_type, salary_min);

-- Add soft delete functionality to major tables
ALTER TABLE job
ADD COLUMN deleted_at DATETIME DEFAULT NULL,
ADD INDEX idx_deleted_at (deleted_at);

ALTER TABLE user
ADD COLUMN deleted_at DATETIME DEFAULT NULL,
ADD INDEX idx_deleted_at (deleted_at);

ALTER TABLE employer
ADD COLUMN deleted_at DATETIME DEFAULT NULL,
ADD INDEX idx_deleted_at (deleted_at);

ALTER TABLE job_seeker
ADD COLUMN deleted_at DATETIME DEFAULT NULL,
ADD INDEX idx_deleted_at (deleted_at);

-- Add full-text search capabilities
ALTER TABLE job 
ADD FULLTEXT INDEX ft_job_search (job_title, job_description, requirements);

-- Add version control for database schema
CREATE TABLE schema_migrations (
    version VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB;

-- Add audit logging
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

-- Add table for rate limiting
CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    requests INT UNSIGNED DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB;