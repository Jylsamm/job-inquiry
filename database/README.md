# WorkConnect PH - Database Documentation

## 📊 Database Overview
- **Database Name**: workconnect_ph
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Storage Engine**: InnoDB
- **Version**: 1.0.0

## 🗃️ Table Structure
- 19 tables total
- Proper foreign key relationships
- Indexed for performance

### Table List:
1. `user` - User accounts
2. `job_seeker` - Job seeker profiles
3. `employer` - Employer/company profiles
4. `job_category` - Job categories
5. `job` - Job postings
6. `job_skill` - Skills required for jobs
7. `application` - Job applications
8. `application_status_history` - Application status tracking
9. `interview` - Interview scheduling
10. `skill` - Master skills list
11. `job_seeker_skill` - Job seeker skills
12. `education` - Education history
13. `work_experience` - Work experience
14. `job_saved` - Saved jobs
15. `job_view` - Job views tracking
16. `notification` - System notifications
17. `message` - User messaging
18. `system_setting` - Application settings
19. `audit_log` - System audit trail

## 🔧 Setup Instructions

### Initial Setup:
1. Place all files in `xampp/htdocs/job-inquiry/`
2. Start XAMPP and ensure MySQL is running
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Create new database: `workconnect_ph`
5. Import `database/workconnect_ph.sql`

### Using Command Line:
```bash
# Navigate to project directory
cd C:\xampp\htdocs\job-inquiry

# Import database using MySQL command line
mysql -u root -p < database/workconnect_ph.sql