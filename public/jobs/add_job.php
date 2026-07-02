<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

/* ==========================
   ✅ COMPANY ACCESS ONLY
========================== */
if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ==========================
   ✅ GET COMPANY ID SAFELY
========================== */
$stmt = $conn->prepare("SELECT id, name FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ Company profile not found.");
}

$companyData = $result->fetch_assoc();
$company_id = $companyData['id'];
$company_name = $companyData['name'];

/* ==========================
   ✅ HANDLE FORM SUBMISSION
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title        = trim($_POST['title']);
    $description  = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $location     = trim($_POST['location']);
    $job_type     = trim($_POST['job_type']);
    $salary_range = trim($_POST['salary_range']); // ✅ CORRECT FIELD

    if ($title == "" || $description == "" || $location == "" || $job_type == "" || $salary_range == "") {
        $error = "All required fields must be filled!";
    } 
    else {
      $stmt = $conn->prepare("
      INSERT INTO jobs 
      (company_id, title, description, requirements, location, job_type, salary_range, status, posted_by)
      VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
      ");


        if (!$stmt) {
            die("SQL Prepare Failed: " . $conn->error);
        }

        $stmt->bind_param(
            "issssssi",
            $company_id,
            $title,
            $description,
            $requirements,
            $location,
            $job_type,
            $salary_range,
            $user_id
        );

        if ($stmt->execute()) {
            header("Location: /job-portal/public/dashboard/company_dashboard.php?job=added");
            exit();
        } else {
            $error = "❌ Failed to post job!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1A535C;
            --primary-light: #4ECDC4;
            --background: #F7FFF7;
            --accent: #FF6B6B;
            --highlight: #FFE66D;
            --gradient: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
            --light-gradient: linear-gradient(135deg, var(--primary-light) 0%, #6bd4cc 100%);
            --accent-gradient: linear-gradient(135deg, var(--accent) 0%, #ff5252 100%);
            --highlight-gradient: linear-gradient(135deg, var(--highlight) 0%, #ffed80 100%);
        }

        body {
            background-color: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .add-job-page {
            min-height: 100vh;
            padding: 30px 0;
        }

        .page-header-company {
            background: var(--gradient);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header-company::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(78, 205, 196, 0.2);
            border-radius: 50%;
        }

        .job-form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 2px solid rgba(26, 83, 92, 0.1);
        }

        .form-section {
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .section-title i {
            font-size: 1.2rem;
            color: var(--primary-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-light);
            width: 20px;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
            background: white;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '▼';
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
            pointer-events: none;
            font-size: 0.8rem;
        }

        .form-control-select {
            appearance: none;
            background-image: none;
            padding-right: 2.5rem;
        }

        .required-field::after {
            content: '*';
            color: var(--accent);
            margin-left: 0.25rem;
        }

        .character-count {
            font-size: 0.8rem;
            color: var(--primary-dark);
            text-align: right;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .submit-btn {
            background: var(--light-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
            box-shadow: 0 8px 20px rgba(78, 205, 196, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(78, 205, 196, 0.4);
            color: white;
            background: var(--light-gradient);
        }

        .flash-message {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .flash-danger {
            border-left: 4px solid var(--accent);
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .form-tips {
            background: var(--highlight-gradient);
            border: 2px solid var(--highlight);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .form-tips h6 {
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }

        .form-tips ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--primary-dark);
        }

        .form-tips li {
            margin-bottom: 0.5rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
        }

        .input-with-icon .form-control {
            padding-left: 3rem;
        }

        .company-badge {
            background: var(--light-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(78, 205, 196, 0.3);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .add-job-page {
                padding: 15px 0;
            }
            
            .page-header-company {
                padding: 1.5rem;
            }
            
            .job-form-container {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="add-job-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Page Header -->
                    <div class="page-header-company">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-2"><i class="fas fa-paper-plane me-2 floating"></i>Post a New Job</h1>
                                <p class="lead mb-0">Create an attractive job listing to find the perfect candidates for your company.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="company-badge">
                                    <i class="fas fa-building"></i>
                                    <?= htmlspecialchars($company_name) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Message -->
                    <?php if (!empty($error)): ?>
                        <div class="alert flash-message flash-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem; color: var(--accent);"></i>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1" style="color: var(--primary-dark);">Attention Required</h5>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Job Form -->
                    <div class="job-form-container">
                        <form method="POST" id="jobForm">

                            <!-- Basic Information Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    Basic Information
                                </h3>

                                <div class="form-group">
                                    <label class="form-label required-field">
                                        <i class="fas fa-heading"></i>
                                        Job Title
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-briefcase"></i>
                                        <input type="text" name="title" class="form-control" placeholder="e.g. Senior Frontend Developer" required maxlength="100">
                                    </div>
                                    <div class="character-count" id="titleCount">0/100 characters</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label required-field">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Job Location
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-location-dot"></i>
                                        <input type="text" name="location" class="form-control" placeholder="e.g. Karachi, Pakistan or Remote" required maxlength="100">
                                    </div>
                                    <div class="character-count" id="locationCount">0/100 characters</div>
                                </div>
                            </div>

                            <!-- Job Details Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-clipboard-list"></i>
                                    Job Details
                                </h3>

                                <div class="form-group">
                                    <label class="form-label required-field">
                                        <i class="fas fa-align-left"></i>
                                        Job Description
                                    </label>
                                    <textarea name="description" class="form-control" placeholder="Describe the role, responsibilities, and what makes this position exciting..." required maxlength="2000"></textarea>
                                    <div class="character-count" id="descriptionCount">0/2000 characters</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-list-check"></i>
                                        Requirements & Qualifications
                                    </label>
                                    <textarea name="requirements" class="form-control" placeholder="List the required skills, experience, and qualifications..." maxlength="1000"></textarea>
                                    <div class="character-count" id="requirementsCount">0/1000 characters</div>
                                </div>
                            </div>

                            <!-- Job Specifications Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-cogs"></i>
                                    Job Specifications
                                </h3>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required-field">
                                                <i class="fas fa-clock"></i>
                                                Job Type
                                            </label>
                                            <div class="select-wrapper">
                                                <select name="job_type" class="form-control form-control-select" required>
                                                    <option value="">Select Job Type</option>
                                                    <option value="Full Time">Full Time</option>
                                                    <option value="Part Time">Part Time</option>
                                                    <option value="Internship">Internship</option>
                                                    <option value="Contract">Contract</option>
                                                    <option value="Freelance">Freelance</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required-field">
                                                <i class="fas fa-money-bill-wave"></i>
                                                Salary Range
                                            </label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-dollar-sign"></i>
                                                <input type="text" name="salary_range" class="form-control" placeholder="e.g. 80,000 - 120,000 PKR" required maxlength="50">
                                            </div>
                                            <div class="character-count" id="salaryCount">0/50 characters</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Tips -->
                            <div class="form-tips">
                                <h6>
                                    <i class="fas fa-lightbulb"></i>
                                    Tips for a Great Job Post
                                </h6>
                                <ul>
                                    <li>Be specific about the role and responsibilities</li>
                                    <li>Highlight your company culture and benefits</li>
                                    <li>Use clear, concise language</li>
                                    <li>Include key requirements and nice-to-haves</li>
                                    <li>Be transparent about salary and benefits</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                Post Job for Approval
                            </button>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
    // Character count functionality
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.querySelector('input[name="title"]');
        const locationInput = document.querySelector('input[name="location"]');
        const descriptionInput = document.querySelector('textarea[name="description"]');
        const requirementsInput = document.querySelector('textarea[name="requirements"]');
        const salaryInput = document.querySelector('input[name="salary_range"]');

        function updateCharacterCount(input, countElement) {
            countElement.textContent = `${input.value.length}/${input.maxLength} characters`;
        }

        // Initialize counts
        updateCharacterCount(titleInput, document.getElementById('titleCount'));
        updateCharacterCount(locationInput, document.getElementById('locationCount'));
        updateCharacterCount(descriptionInput, document.getElementById('descriptionCount'));
        updateCharacterCount(requirementsInput, document.getElementById('requirementsCount'));
        updateCharacterCount(salaryInput, document.getElementById('salaryCount'));

        // Add event listeners
        titleInput.addEventListener('input', () => updateCharacterCount(titleInput, document.getElementById('titleCount')));
        locationInput.addEventListener('input', () => updateCharacterCount(locationInput, document.getElementById('locationCount')));
        descriptionInput.addEventListener('input', () => updateCharacterCount(descriptionInput, document.getElementById('descriptionCount')));
        requirementsInput.addEventListener('input', () => updateCharacterCount(requirementsInput, document.getElementById('requirementsCount')));
        salaryInput.addEventListener('input', () => updateCharacterCount(salaryInput, document.getElementById('salaryCount')));

        // Form submission enhancement
        const form = document.getElementById('jobForm');
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting Job...';
            submitBtn.disabled = true;
        });
    });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>