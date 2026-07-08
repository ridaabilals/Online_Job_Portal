# 🎯 CareerConnect - Job Portal

A comprehensive, full-featured job portal platform built with PHP and MySQL. Connect job seekers with companies and streamline the recruitment process with an intuitive, modern interface.

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [Usage Guide](#usage-guide)
- [API Endpoints](#api-endpoints)
- [File Upload](#file-upload)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### For Job Seekers
- 🔑 User registration and authentication
- 👤 Comprehensive profile management
- 📄 Resume/CV upload and management
- 🔍 Advanced job search and filtering
- 📧 Apply to job listings
- 📊 Track application status (pending, shortlisted, hired, rejected)
- 🔔 Notification system for updates
- ⭐ Save favorite job listings
- 📱 Responsive mobile-friendly interface

### For Companies
- 🏢 Company profile creation and management
- 💼 Post and manage job listings
- 📋 View and manage job applications
- 👥 Applicant tracking with status updates
- 📊 Application statistics and analytics
- ✅ Approve/reject job postings
- 🎯 Shortlist candidates
- ✈️ Hire candidates and track hires
- 📝 Detailed application reviews

### For Administrators
- 👨‍💼 Manage user accounts
- 🏢 Manage company profiles
- 💼 Approve/reject job listings
- 👥 Monitor all applications
- 🎖️ Promote users to admin status
- 📊 System-wide analytics and reports
- 🗑️ Delete users, companies, or jobs

## 🛠️ Tech Stack

| Component | Technology |
|-----------|------------|
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Framework** | Bootstrap 5.3.0 |
| **Icons** | Font Awesome 6.0.0 |
| **Server** | Apache (via XAMPP) |

## 📦 System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Server**: Apache with mod_rewrite enabled
- **Disk Space**: Minimum 100MB (for database and file uploads)
- **RAM**: Minimum 512MB
- **XAMPP**: Latest version recommended

## 🚀 Installation

### 1. Extract Project Files
```bash
# Extract the project to XAMPP htdocs directory
cd C:\xampp\htdocs
# Extract job-portal.zip to this location
```

### 2. Create Database
```bash
# Open phpMyAdmin
# http://localhost/phpmyadmin

# Create new database: job_portal
# Go to SQL tab and import the schema file:
# sql/job_portal_schema.sql
```

### 3. Configure Database Connection (Optional)
Edit `src/config.php` if using non-default database credentials:
```php
<?php
// src/config.php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASSWORD = "";
$DB_NAME = "job_portal";
?>
```

### 4. Set File Upload Permissions
```bash
# Create uploads directory if it doesn't exist
mkdir -p public/assets/uploads/resumes
mkdir -p public/assets/uploads/company_logos

# Set permissions (Windows: Not required, Unix/Linux:)
chmod 755 public/assets/uploads
chmod 755 public/assets/uploads/resumes
chmod 755 public/assets/uploads/company_logos
```

### 5. Start XAMPP Services
- Start Apache and MySQL from XAMPP Control Panel
- Navigate to `http://localhost/job-portal/public/`

## 📂 Project Structure

```
job-portal/
├── public/                          # Web-accessible directory
│   ├── index.php                   # Home page
│   ├── login.php                   # User login
│   ├── register.php                # User registration
│   ├── jobs.php                    # Job listings (all jobs)
│   ├── job_view.php                # Individual job details
│   ├── company_list.php            # Browse companies
│   ├── logout.php                  # Logout handler
│   │
│   ├── admin/                      # Admin panel
│   │   ├── manage_users.php        # Manage user accounts
│   │   ├── manage_jobs.php         # Manage all jobs
│   │   ├── manage_companies.php    # Manage companies
│   │   ├── pending_jobs.php        # Review pending jobs
│   │   ├── rejected_jobs.php       # View rejected jobs
│   │   ├── approve_job.php         # Approve job listings
│   │   ├── reject_job_action.php   # Reject job listings
│   │   └── make_admin.php          # Promote users to admin
│   │
│   ├── applications/               # Job application handling
│   │   ├── apply.php               # Submit job application
│   │   ├── my_applications.php     # View company applications
│   │   ├── upload_cv.php           # Upload CV/Resume
│   │   ├── hire_action.php         # Hire applicant
│   │   ├── reject_action.php       # Reject application
│   │   └── shortlist_action.php    # Shortlist applicant
│   │
│   ├── company/                    # Company management
│   │   ├── create_company.php      # Create company profile
│   │   ├── edit_company.php        # Edit company information
│   │   └── my_company.php          # View company profile
│   │
│   ├── dashboard/                  # User dashboards
│   │   ├── admin_dashboard.php     # Admin dashboard
│   │   ├── company_dashboard.php   # Company dashboard
│   │   └── user_dashboard.php      # Job seeker dashboard
│   │
│   ├── jobs/                       # Job management
│   │   ├── add_job.php             # Create new job
│   │   ├── edit_job.php            # Edit job listing
│   │   ├── delete_job.php          # Delete job
│   │   └── my_jobs.php             # Company's jobs
│   │
│   ├── profile/                    # User profile
│   │   └── edit_profile.php        # Edit profile information
│   │
│   ├── notifications/              # Notification handling
│   │   └── mark_read.php           # Mark notification as read
│   │
│   ├── includes/                   # Shared components
│   │   ├── header.php              # Page header
│   │   ├── navbar.php              # Navigation bar
│   │   └── footer.php              # Page footer
│   │
│   └── assets/                     # Static files
│       ├── css/
│       │   ├── bootstrap.min.css   # Bootstrap framework
│       │   └── style.css           # Custom styles
│       ├── js/
│       │   └── validation.js       # Form validation
│       └── uploads/
│           ├── resumes/            # User resume storage
│           └── company_logos/      # Company logo storage
│
├── src/                            # Backend logic
│   ├── config.php                  # Database configuration
│   ├── db.php                      # Database connection
│   ├── auth.php                    # Authentication logic
│   │
│   ├── controllers/                # Business logic
│   │   ├── UserController.php      # User operations
│   │   ├── JobController.php       # Job operations
│   │   ├── ApplicationController.php
│   │   └── CompanyController.php   # Company operations
│   │
│   ├── helpers/                    # Utility functions
│   │   ├── validation_helper.php   # Form validation
│   │   └── file_upload_helper.php  # File upload utilities
│   │
│   └── middleware/                 # Request middleware
│       ├── auth_middleware.php     # Authentication check
│       └── admin_middleware.php    # Admin access check
│
├── sql/                            # Database
│   └── job_portal_schema.sql       # Database schema and tables
│
└── README.md                       # This file
```

## 👥 User Roles

### 1. **Job Seeker**
- Registration with email verification
- Profile management (name, phone, qualifications)
- Resume upload
- Job search and filtering
- Apply to jobs
- Track application status
- View company profiles

### 2. **Company**
- Company registration and profile setup
- Post job listings (requires admin approval)
- Manage applications
- Shortlist candidates
- Hire applicants
- View application history
- Dashboard with statistics

### 3. **Administrator**
- Full system access
- User management (create, delete, promote)
- Company management
- Job listing approval/rejection
- Application monitoring
- System analytics

### 4. **Super Admin** (Optional)
- All admin permissions
- User role management
- System configuration
- Advanced reporting

## 📖 Usage Guide

### For Job Seekers

#### Registration
1. Click "Register" on home page
2. Choose "Job Seeker" option
3. Fill in email, username, password
4. Verify email (if implemented)
5. Complete your profile with full name, phone, qualifications

#### Finding Jobs
1. Navigate to "Find Jobs" or "Jobs" page
2. Use search bar to filter by title, location, company
3. Sort by newest, salary, or most popular
4. Click job card to view full details
5. Click "Apply Now" to submit application

#### Uploading Resume
1. Go to "Profile" or "Dashboard"
2. Click "Upload Resume" or "Manage Resume"
3. Select PDF, DOC, or DOCX file (max 5MB)
4. Resume is automatically attached to applications

#### Tracking Applications
1. Go to "My Applications" or "Dashboard"
2. View status of all submitted applications:
   - **Pending**: Waiting for company review
   - **Shortlisted**: Company is interested
   - **Hired**: Congratulations! You got the job
   - **Rejected**: Application declined

### For Companies

#### Registration
1. Click "Register" on home page
2. Choose "Company" option
3. Create account with email and password
4. Set up company profile (name, website, description)
5. Verify company details

#### Posting Jobs
1. Go to Dashboard → "Post New Job"
2. Fill in job details:
   - Title, Location, Job Type, Salary Range
   - Description, Requirements, Benefits
3. Submit for admin approval
4. Wait for approval (check status in "My Jobs")

#### Managing Applications
1. Go to "My Applications" or Dashboard
2. View all applicants for each job
3. Actions available:
   - **Shortlist**: Mark as potential candidate
   - **Hire**: Offer position to applicant
   - **Reject**: Decline application
4. View applicant resume and details

#### Monitoring Performance
1. Dashboard shows:
   - Total jobs posted
   - Pending approvals
   - Total applications
   - Shortlisted candidates
   - Hired employees
2. Track application trends over time

### For Administrators

#### User Management
1. Go to Admin Panel → "Manage Users"
2. View all registered users
3. Delete users if necessary
4. Promote regular users to admin

#### Job Approval
1. Go to "Pending Jobs" tab
2. Review job details before approval
3. Approve or reject jobs
4. Rejected jobs can be reviewed in "Rejected Jobs"

#### Company Management
1. View all registered companies
2. Verify company information
3. Delete companies if violating terms

#### Monitoring Applications
1. View all applications across the platform
2. Monitor application trends
3. Generate reports on platform usage

## 🔌 API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/public/login.php` | User login |
| POST | `/public/register.php` | User registration |
| GET | `/public/logout.php` | User logout |

### Jobs
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/public/jobs.php` | List all jobs |
| GET | `/public/job_view.php?id=X` | View job details |
| POST | `/public/jobs/add_job.php` | Create new job |
| POST | `/public/jobs/edit_job.php?id=X` | Update job |
| GET | `/public/jobs/delete_job.php?id=X` | Delete job |
| GET | `/public/jobs/my_jobs.php` | Company's jobs |

### Applications
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/public/applications/apply.php` | Apply to job |
| GET | `/public/applications/my_applications.php` | View applications |
| POST | `/public/applications/hire_action.php` | Hire applicant |
| POST | `/public/applications/reject_action.php` | Reject application |
| POST | `/public/applications/shortlist_action.php` | Shortlist candidate |

### Users
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/public/profile/edit_profile.php` | Edit user profile |
| POST | `/public/applications/upload_cv.php` | Upload CV |

## 📤 File Upload

### Resume Upload
- **Accepted Formats**: PDF, DOC, DOCX
- **Maximum Size**: 5MB
- **Storage Location**: `/public/assets/uploads/resumes/`
- **Naming**: `user_id_timestamp.extension`

### Company Logo Upload
- **Accepted Formats**: JPG, PNG, GIF
- **Maximum Size**: 2MB
- **Storage Location**: `/public/assets/uploads/company_logos/`

### Validation Rules
```php
// File type validation
$allowed_types = ['application/pdf', 'application/msword', 
                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

// Size validation (5MB max)
$max_size = 5 * 1024 * 1024; // 5MB
```

## 🔒 Security

### Implemented Security Measures

1. **SQL Injection Prevention**
   - Prepared statements with parameterized queries
   - Input sanitization with `mysqli_real_escape_string()`

2. **Authentication & Authorization**
   - Session-based authentication
   - Role-based access control
   - Password hashing with `password_hash()`

3. **Input Validation**
   - Server-side form validation
   - Email validation
   - File type and size validation

4. **File Security**
   - File upload restrictions
   - Unique file naming
   - Secure file storage outside webroot

5. **CSRF Protection**
   - Session validation for form submissions
   - Token-based verification (can be enhanced)

6. **XSS Prevention**
   - Output escaping with `htmlspecialchars()`
   - Content Security Policy (recommended)

### Recommendations for Production

```php
// Use prepared statements (already implemented)
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// Validate and sanitize inputs
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

// Hash passwords
$hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Use environment variables for sensitive data
require_once __DIR__ . '/.env';
```

## 🐛 Troubleshooting

### Database Connection Error
```
Error: Database Connection Failed
```
**Solution**:
- Verify MySQL is running
- Check database credentials in `src/db.php`
- Ensure database `job_portal` exists
- Run `sql/job_portal_schema.sql` to create tables

### File Upload Not Working
```
Error: Failed to upload file
```
**Solution**:
- Check folder permissions: `chmod 755 public/assets/uploads/`
- Verify upload_max_filesize in php.ini (minimum 5MB)
- Ensure sufficient disk space

### Login Issues
```
Error: Invalid credentials or access denied
```
**Solution**:
- Clear browser cookies and session cache
- Verify user account exists
- Check if account is active/not deleted
- Verify role/permissions are correct

### White Blank Page
```
Blank page with no error
```
**Solution**:
- Enable PHP error reporting: `error_reporting(E_ALL);`
- Check PHP error logs in XAMPP
- Verify file path includes are correct
- Check database connection

### Session Timeout
```
Redirect to login page unexpectedly
```
**Solution**:
- Increase session timeout in `php.ini`
- Set `session.gc_maxlifetime = 3600` (1 hour)
- Check for multiple tabs interfering with session

## 🤝 Contributing

### How to Contribute

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Make your changes
4. Commit changes (`git commit -m 'Add some AmazingFeature'`)
5. Push to branch (`git push origin feature/AmazingFeature`)
6. Open a Pull Request

### Coding Standards

- Follow PSR-2 PHP coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Test changes before submitting
- Update documentation as needed

### Reporting Bugs

1. Check if bug already exists
2. Provide detailed description
3. Include steps to reproduce
4. Attach screenshots if applicable
5. Specify PHP version and environment

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**CareerConnect Team**
- Project: Job Portal Platform
- Version: 1.0.0
- Last Updated: 2024

## 🙏 Acknowledgments

- Bootstrap team for the CSS framework
- Font Awesome for icons
- PHP community for documentation
- All contributors and testers

## 📞 Support

For support, email: ridaab25@gmail.com
Or create an issue in the project repository.

---

**Happy Recruiting! 🎯**
