-- =============================================
-- WorkConnect PH - Complete Database Setup
-- File: database/workconnect_ph.sql
-- Description: Main database schema with sample data
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS workconnect_ph;
CREATE DATABASE workconnect_ph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE workconnect_ph;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 1. Core User Tables
-- =============================================

CREATE TABLE user (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_picture VARCHAR(500),
    user_type ENUM('job_seeker', 'employer', 'admin') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE job_seeker (
    job_seeker_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    headline VARCHAR(255),
    bio TEXT,
    location VARCHAR(255),
    resume_file VARCHAR(500),
    website_url VARCHAR(500),
    linkedin_url VARCHAR(500),
    expected_salary DECIMAL(10,2),
    experience_level ENUM('entry', 'mid', 'senior', 'executive'),
    education_level ENUM('high_school', 'associate', 'bachelor', 'master', 'phd'),
    open_to_work BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    INDEX idx_location (location),
    INDEX idx_experience_level (experience_level),
    INDEX idx_open_to_work (open_to_work)
) ENGINE=InnoDB;

CREATE TABLE employer (
    employer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    company_logo VARCHAR(500),
    company_description TEXT,
    industry VARCHAR(100),
    website_url VARCHAR(500),
    company_size ENUM('1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'),
    founded_year YEAR,
    tax_id VARCHAR(50),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    INDEX idx_company_name (company_name),
    INDEX idx_industry (industry),
    INDEX idx_is_verified (is_verified)
) ENGINE=InnoDB;

-- =============================================
-- 2. Job Management Tables
-- =============================================

CREATE TABLE job_category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    category_description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_category_name (category_name)
) ENGINE=InnoDB;

CREATE TABLE job (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT NOT NULL,
    category_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    job_description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    responsibilities TEXT,
    benefits TEXT,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    salary_type ENUM('hourly', 'monthly', 'yearly') DEFAULT 'monthly',
    currency VARCHAR(3) DEFAULT 'PHP',
    job_type ENUM('full_time', 'part_time', 'contract', 'internship', 'remote', 'hybrid') NOT NULL,
    experience_level ENUM('entry', 'mid', 'senior', 'executive') NOT NULL,
    education_required ENUM('none', 'high_school', 'associate', 'bachelor', 'master', 'phd'),
    location VARCHAR(255) NOT NULL,
    is_remote BOOLEAN DEFAULT FALSE,
    application_deadline DATE,
    positions_available INT DEFAULT 1,
    application_url VARCHAR(500),
    status ENUM('draft', 'pending', 'published', 'closed', 'expired') DEFAULT 'draft',
    views_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employer_id) REFERENCES employer(employer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_category(category_id),
    INDEX idx_job_title (job_title),
    INDEX idx_location (location),
    INDEX idx_job_type (job_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_application_deadline (application_deadline),
    FULLTEXT idx_search (job_title, job_description, requirements, location)
) ENGINE=InnoDB;

CREATE TABLE job_skill (
    job_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    importance_level ENUM('required', 'preferred', 'bonus') DEFAULT 'required',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES job(job_id) ON DELETE CASCADE,
    INDEX idx_skill_name (skill_name),
    UNIQUE KEY unique_job_skill (job_id, skill_name)
) ENGINE=InnoDB;

-- =============================================
-- 3. Application & Matching System
-- =============================================

CREATE TABLE application (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    job_seeker_id INT NOT NULL,
    cover_letter TEXT,
    resume_file VARCHAR(500),
    expected_salary DECIMAL(10,2),
    availability_date DATE,
    status ENUM('submitted', 'under_review', 'shortlisted', 'interview', 'rejected', 'accepted', 'withdrawn') DEFAULT 'submitted',
    status_notes TEXT,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES job(job_id) ON DELETE CASCADE,
    FOREIGN KEY (job_seeker_id) REFERENCES job_seeker(job_seeker_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_applied_at (applied_at),
    UNIQUE KEY unique_application (job_id, job_seeker_id)
) ENGINE=InnoDB;

CREATE TABLE application_status_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    old_status ENUM('submitted', 'under_review', 'shortlisted', 'interview', 'rejected', 'accepted', 'withdrawn'),
    new_status ENUM('submitted', 'under_review', 'shortlisted', 'interview', 'rejected', 'accepted', 'withdrawn') NOT NULL,
    change_notes TEXT,
    changed_by_user_id INT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES application(application_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES user(user_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB;

CREATE TABLE interview (
    interview_id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    interview_date DATETIME NOT NULL,
    interview_type ENUM('phone', 'video', 'in_person') DEFAULT 'video',
    interview_location VARCHAR(500),
    interview_notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    feedback_notes TEXT,
    rating TINYINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES application(application_id) ON DELETE CASCADE,
    INDEX idx_interview_date (interview_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =============================================
-- 4. Skills & Qualifications
-- =============================================

CREATE TABLE skill (
    skill_id INT PRIMARY KEY AUTO_INCREMENT,
    skill_name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_skill_name (skill_name),
    INDEX idx_category (category)
) ENGINE=InnoDB;

CREATE TABLE job_seeker_skill (
    job_seeker_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    job_seeker_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    years_of_experience DECIMAL(3,1),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_seeker_id) REFERENCES job_seeker(job_seeker_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(skill_id),
    INDEX idx_proficiency_level (proficiency_level),
    UNIQUE KEY unique_job_seeker_skill (job_seeker_id, skill_id)
) ENGINE=InnoDB;

CREATE TABLE education (
    education_id INT PRIMARY KEY AUTO_INCREMENT,
    job_seeker_id INT NOT NULL,
    institution_name VARCHAR(255) NOT NULL,
    degree VARCHAR(100),
    field_of_study VARCHAR(100),
    grade VARCHAR(50),
    start_date DATE,
    end_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_seeker_id) REFERENCES job_seeker(job_seeker_id) ON DELETE CASCADE,
    INDEX idx_institution_name (institution_name),
    INDEX idx_degree (degree)
) ENGINE=InnoDB;

CREATE TABLE work_experience (
    experience_id INT PRIMARY KEY AUTO_INCREMENT,
    job_seeker_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    description TEXT,
    achievements TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_seeker_id) REFERENCES job_seeker(job_seeker_id) ON DELETE CASCADE,
    INDEX idx_company_name (company_name),
    INDEX idx_job_title (job_title)
) ENGINE=InnoDB;

-- =============================================
-- 5. Interactions & Notifications
-- =============================================

CREATE TABLE job_saved (
    saved_id INT PRIMARY KEY AUTO_INCREMENT,
    job_seeker_id INT NOT NULL,
    job_id INT NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_seeker_id) REFERENCES job_seeker(job_seeker_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES job(job_id) ON DELETE CASCADE,
    UNIQUE KEY unique_job_saved (job_seeker_id, job_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE job_view (
    view_id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES job(job_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    INDEX idx_viewed_at (viewed_at),
    INDEX idx_job_user (job_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE notification (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('application', 'interview', 'message', 'system', 'job_alert') NOT NULL,
    related_entity_type ENUM('job', 'application', 'interview', 'message'),
    related_entity_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_notification_type (notification_type)
) ENGINE=InnoDB;

CREATE TABLE message (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255),
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    parent_message_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES message(message_id),
    INDEX idx_sender_receiver (sender_id, receiver_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB;

-- =============================================
-- 6. System & Admin Tables
-- =============================================

CREATE TABLE system_setting (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by_user_id INT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by_user_id) REFERENCES user(user_id),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

-- =============================================
-- SAMPLE DATA INSERTION
-- =============================================

-- Insert job categories
INSERT INTO job_category (category_name, category_description) VALUES
('Information Technology', 'Software development, IT support, networking, and technology roles'),
('Healthcare', 'Medical, nursing, healthcare administration, and wellness positions'),
('Education', 'Teaching, academic administration, and educational support roles'),
('Finance', 'Banking, accounting, financial analysis, and investment roles'),
('Marketing', 'Digital marketing, advertising, brand management, and PR roles'),
('Sales', 'Business development, account management, and sales positions'),
('Engineering', 'Civil, mechanical, electrical, and other engineering disciplines'),
('Customer Service', 'Customer support, client services, and help desk roles'),
('Design', 'Graphic design, UI/UX, product design, and creative roles'),
('Human Resources', 'Recruitment, HR management, talent development, and training');

-- Insert skills
INSERT INTO skill (skill_name, category) VALUES
-- Technical Skills
('JavaScript', 'Programming'),
('Python', 'Programming'),
('Java', 'Programming'),
('PHP', 'Programming'),
('React', 'Frontend'),
('Vue.js', 'Frontend'),
('Node.js', 'Backend'),
('MySQL', 'Database'),
('MongoDB', 'Database'),
('AWS', 'Cloud'),
('Docker', 'DevOps'),
('Git', 'Tools'),
-- Soft Skills
('Communication', 'Soft Skills'),
('Leadership', 'Soft Skills'),
('Problem Solving', 'Soft Skills'),
('Teamwork', 'Soft Skills'),
('Time Management', 'Soft Skills'),
('Creativity', 'Soft Skills'),
-- Business Skills
('Project Management', 'Business'),
('Agile Methodology', 'Business'),
('Scrum', 'Business'),
('Digital Marketing', 'Marketing'),
('SEO', 'Marketing'),
('Content Writing', 'Marketing');

-- Insert sample users (password is 'password123' hashed with bcrypt)
INSERT INTO user (email, password_hash, first_name, last_name, phone, user_type, is_active, email_verified) VALUES
-- Job Seekers
('maria.santos@email.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', '+639171234567', 'job_seeker', TRUE, TRUE),
('juan.dela.cruz@email.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Dela Cruz', '+639181234568', 'job_seeker', TRUE, TRUE),
('ana.reyes@email.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana', 'Reyes', '+639191234569', 'job_seeker', TRUE, TRUE),
-- Employers
('hr@techcorp.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TechCorp', 'HR', '+639201234570', 'employer', TRUE, TRUE),
('careers@creativesinc.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Creatives', 'Inc', '+639211234571', 'employer', TRUE, TRUE),
('admin@buildright.com', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BuildRight', 'Construction', '+639221234572', 'employer', TRUE, TRUE),
-- Admin
('admin@workconnect.ph', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '+639231234573', 'admin', TRUE, TRUE);

-- Insert job seekers
INSERT INTO job_seeker (user_id, headline, bio, location, expected_salary, experience_level, education_level, open_to_work) VALUES
(1, 'Full Stack Developer', 'Experienced full stack developer with 5+ years in web development. Passionate about creating scalable applications and solving complex problems.', 'Manila, Philippines', 60000.00, 'senior', 'bachelor', TRUE),
(2, 'Graphic Designer', 'Creative graphic designer specializing in brand identity and digital design. Strong portfolio of successful projects for various clients.', 'Cebu, Philippines', 35000.00, 'mid', 'bachelor', TRUE),
(3, 'Project Manager', 'PMP certified project manager with expertise in agile methodologies. Successfully delivered 50+ projects on time and within budget.', 'Davao, Philippines', 80000.00, 'senior', 'master', TRUE);

-- Insert employers
INSERT INTO employer (user_id, company_name, company_description, industry, website_url, company_size, founded_year, is_verified) VALUES
(4, 'TechCorp Solutions', 'Leading technology company specializing in software development and digital transformation services. We build innovative solutions for businesses worldwide.', 'Information Technology', 'https://techcorp.com', '201-500', 2010, TRUE),
(5, 'Creatives Inc.', 'Award-winning creative agency providing branding, design, and marketing services to top brands across Southeast Asia.', 'Marketing', 'https://creativesinc.com', '51-200', 2015, TRUE),
(6, 'BuildRight Construction', 'Premium construction company with 20+ years of experience in commercial and residential projects. Committed to quality and safety.', 'Construction', 'https://buildright.com', '501-1000', 2000, TRUE);

-- Insert job seeker skills
INSERT INTO job_seeker_skill (job_seeker_id, skill_id, proficiency_level, years_of_experience, is_primary) VALUES
-- Maria Santos (Full Stack Developer)
(1, 1, 'expert', 5.0, TRUE),  -- JavaScript
(1, 2, 'advanced', 4.0, TRUE),  -- Python
(1, 5, 'expert', 5.0, TRUE),  -- React
(1, 7, 'advanced', 4.0, FALSE), -- Node.js
(1, 8, 'advanced', 5.0, FALSE), -- MySQL
(1, 13, 'advanced', 5.0, FALSE), -- Communication
-- Juan Dela Cruz (Graphic Designer)
(2, 22, 'expert', 4.0, TRUE),  -- Content Writing
(2, 16, 'advanced', 4.0, FALSE), -- Creativity
(2, 13, 'advanced', 4.0, FALSE), -- Communication
-- Ana Reyes (Project Manager)
(3, 18, 'expert', 8.0, TRUE),  -- Project Management
(3, 19, 'expert', 6.0, TRUE),  -- Agile Methodology
(3, 13, 'expert', 8.0, FALSE), -- Communication
(3, 14, 'advanced', 6.0, FALSE); -- Leadership

-- Insert education records
INSERT INTO education (job_seeker_id, institution_name, degree, field_of_study, start_date, end_date, is_current) VALUES
(1, 'University of the Philippines', 'Bachelor of Science', 'Computer Science', '2014-06-01', '2018-03-01', FALSE),
(2, 'University of San Carlos', 'Bachelor of Fine Arts', 'Graphic Design', '2015-06-01', '2019-03-01', FALSE),
(3, 'Ateneo de Manila University', 'Master of Business Administration', 'Business Management', '2012-06-01', '2014-03-01', FALSE);

-- Insert work experience
INSERT INTO work_experience (job_seeker_id, company_name, job_title, location, start_date, end_date, is_current, description) VALUES
(1, 'Previous Tech Company', 'Senior Developer', 'Makati, Philippines', '2019-04-01', '2023-12-01', FALSE, 'Led development of multiple web applications using React and Node.js'),
(2, 'Design Studio Co.', 'Graphic Designer', 'Cebu, Philippines', '2019-05-01', '2023-11-01', FALSE, 'Created brand identities and marketing materials for various clients'),
(3, 'Large Corporation', 'Project Manager', 'Taguig, Philippines', '2018-03-01', '2023-12-01', FALSE, 'Managed software development projects with teams of 10-20 people');

-- Insert jobs
INSERT INTO job (
    employer_id, category_id, job_title, job_description, requirements, 
    salary_min, salary_max, job_type, experience_level, education_required,
    location, is_remote, application_deadline, positions_available, status
) VALUES
-- TechCorp Jobs
(1, 1, 'Senior Full Stack Developer', 'We are looking for an experienced Full Stack Developer to join our dynamic team. You will be responsible for developing and maintaining web applications using modern technologies.',
 '5+ years of experience in full stack development\nStrong proficiency in JavaScript, React, and Node.js\nExperience with databases (MySQL, MongoDB)\nKnowledge of cloud platforms (AWS)\nExcellent problem-solving skills',
 50000.00, 80000.00, 'full_time', 'senior', 'bachelor', 'Manila, Philippines', TRUE, '2024-03-31', 2, 'published'),

(1, 1, 'Junior Frontend Developer', 'Great opportunity for a Junior Frontend Developer to grow their skills in a supportive environment. You will work with our senior developers on exciting projects.',
 '1-2 years of experience in frontend development\nProficiency in HTML, CSS, JavaScript\nFamiliarity with React or Vue.js\nGood understanding of responsive design\nWillingness to learn and grow',
 25000.00, 40000.00, 'full_time', 'entry', 'bachelor', 'Manila, Philippines', TRUE, '2024-04-15', 3, 'published'),

-- Creatives Inc Jobs
(2, 9, 'Senior Graphic Designer', 'Join our creative team as a Senior Graphic Designer. You will lead design projects and create compelling visual identities for our clients.',
 '4+ years of professional design experience\nExpertise in Adobe Creative Suite\nStrong portfolio of design work\nExperience in brand identity development\nExcellent communication and presentation skills',
 35000.00, 55000.00, 'full_time', 'senior', 'bachelor', 'Cebu, Philippines', TRUE, '2024-04-10', 1, 'published'),

(2, 5, 'Digital Marketing Specialist', 'We are seeking a Digital Marketing Specialist to develop and implement effective digital marketing strategies for our clients.',
 '3+ years of digital marketing experience\nProficiency in SEO, SEM, and social media marketing\nExperience with analytics tools (Google Analytics)\nContent creation and copywriting skills\nKnowledge of marketing automation tools',
 30000.00, 45000.00, 'full_time', 'mid', 'bachelor', 'Cebu, Philippines', FALSE, '2024-04-05', 2, 'published'),

-- BuildRight Jobs
(3, 7, 'Civil Engineer', 'Looking for a qualified Civil Engineer to join our construction team. You will be involved in planning, design, and supervision of construction projects.',
 'Bachelor''s degree in Civil Engineering\nProfessional license required\n3+ years of construction experience\nKnowledge of AutoCAD and other design software\nStrong project management skills',
 40000.00, 60000.00, 'full_time', 'mid', 'bachelor', 'Davao, Philippines', FALSE, '2024-04-20', 2, 'published');

-- Insert job skills
INSERT INTO job_skill (job_id, skill_name, importance_level) VALUES
-- Senior Full Stack Developer skills
(1, 'JavaScript', 'required'),
(1, 'React', 'required'),
(1, 'Node.js', 'required'),
(1, 'MySQL', 'required'),
(1, 'AWS', 'preferred'),
(1, 'Problem Solving', 'required'),

-- Junior Frontend Developer skills
(2, 'JavaScript', 'required'),
(2, 'React', 'preferred'),
(2, 'HTML', 'required'),
(2, 'CSS', 'required'),
(2, 'Communication', 'required'),

-- Senior Graphic Designer skills
(3, 'Creativity', 'required'),
(3, 'Adobe Creative Suite', 'required'),
(3, 'Communication', 'required'),
(3, 'Content Writing', 'preferred'),

-- Digital Marketing Specialist skills
(4, 'Digital Marketing', 'required'),
(4, 'SEO', 'required'),
(4, 'Content Writing', 'required'),
(4, 'Google Analytics', 'preferred'),

-- Civil Engineer skills
(5, 'Project Management', 'required'),
(5, 'AutoCAD', 'required'),
(5, 'Problem Solving', 'required'),
(5, 'Communication', 'required');

-- Insert applications
INSERT INTO application (job_id, job_seeker_id, cover_letter, expected_salary, status) VALUES
(1, 1, 'I am excited to apply for the Senior Full Stack Developer position. With my 5+ years of experience and expertise in React and Node.js, I believe I would be a great fit for your team.', 65000.00, 'shortlisted'),
(3, 2, 'As an experienced graphic designer with a strong portfolio, I am confident in my ability to contribute to your creative team and deliver outstanding design solutions for your clients.', 45000.00, 'submitted'),
(5, 3, 'With my PMP certification and extensive project management experience, I am well-equipped to handle the responsibilities of the Civil Engineer position and ensure successful project delivery.', 70000.00, 'under_review');

-- Insert saved jobs
INSERT INTO job_saved (job_seeker_id, job_id) VALUES
(1, 2),  -- Maria saved Junior Frontend Developer
(2, 4),  -- Juan saved Digital Marketing Specialist
(3, 1);  -- Ana saved Senior Full Stack Developer

-- Insert notifications
INSERT INTO notification (user_id, title, message, notification_type, related_entity_type, related_entity_id) VALUES
(1, 'Application Shortlisted', 'Your application for Senior Full Stack Developer at TechCorp has been shortlisted!', 'application', 'application', 1),
(2, 'New Job Match', 'A new Graphic Designer position at Creatives Inc. matches your profile!', 'job_alert', 'job', 3),
(3, 'Application Received', 'BuildRight Construction has received your application for Civil Engineer.', 'application', 'application', 3);

-- Insert system settings
INSERT INTO system_setting (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'WorkConnect PH', 'string', 'The name of the job portal'),
('site_description', 'Connecting Filipino talent with great opportunities', 'string', 'The description of the job portal'),
('application_fee', '0', 'integer', 'Application fee amount in PHP'),
('max_job_postings', '10', 'integer', 'Maximum job postings per employer per month'),
('auto_approve_jobs', 'false', 'boolean', 'Automatically approve job postings without admin review');

-- Insert sample messages
INSERT INTO message (sender_id, receiver_id, subject, message_text) VALUES
(4, 1, 'Interview Invitation', 'Hello Maria! We were impressed with your application and would like to invite you for an interview. Please let us know your availability.'),
(1, 4, 'Re: Interview Invitation', 'Thank you for the invitation! I am available next week Monday to Wednesday. Looking forward to discussing the opportunity.');

COMMIT;

SELECT 'WorkConnect PH Database Successfully Created!' AS message;