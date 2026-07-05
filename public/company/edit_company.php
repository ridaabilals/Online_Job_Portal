<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ✅ FETCH COMPANY */
$stmt = $conn->prepare("SELECT * FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Company profile not found.");
}

$company = $res->fetch_assoc();

/* ✅ HANDLE UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $website = trim($_POST['website'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $description = trim($_POST['description']);

    if ($name == "") {
        $error = "Company name is required!";
    } else {
            // Make sure columns exist (development-time convenience)
            $col = $conn->query("SHOW COLUMNS FROM companies LIKE 'website'");
            if ($col && $col->num_rows === 0) {
                $conn->query("ALTER TABLE companies ADD COLUMN website VARCHAR(255) DEFAULT NULL");
            }
            $col2 = $conn->query("SHOW COLUMNS FROM companies LIKE 'industry'");
            if ($col2 && $col2->num_rows === 0) {
                $conn->query("ALTER TABLE companies ADD COLUMN industry VARCHAR(255) DEFAULT NULL");
            }

        $update = $conn->prepare("
            UPDATE companies 
            SET name = ?, website = ?, industry = ?, description = ?
            WHERE id = ?
        ");

        $update->bind_param("ssssi", $name, $website, $industry, $description, $company['id']);

        if ($update->execute()) {
            header("Location: /job-portal/public/dashboard/company_dashboard.php?company=updated");
            exit;
        } else {
            $error = "Failed to update company!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company Profile - Job Portal</title>
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

        .edit-company-page {
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

        .profile-form-container {
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
            min-height: 120px;
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

        .company-avatar {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: var(--light-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin-right: 1.5rem;
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .company-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(247, 255, 247, 0.8) 0%, rgba(255, 255, 255, 0.9) 100%);
            border-radius: 15px;
            border-left: 4px solid var(--primary-light);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.1);
        }

        .company-info h4 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .company-info p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
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

        .industry-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .industry-tag {
            background: rgba(26, 83, 92, 0.1);
            color: var(--primary-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid rgba(26, 83, 92, 0.2);
        }

        .industry-tag:hover {
            background: var(--primary-light);
            color: white;
            transform: translateY(-1px);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .edit-company-page {
                padding: 15px 0;
            }
            
            .page-header-company {
                padding: 1.5rem;
            }
            
            .profile-form-container {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .company-header {
                flex-direction: column;
                text-align: center;
            }
            
            .company-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>
    <?php include "../includes/navbar.php"; ?>

    <div class="edit-company-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Page Header -->
                    <div class="page-header-company">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-2"><i class="fas fa-building me-2 floating"></i>Edit Company Profile</h1>
                                <p class="lead mb-0">Update your company information to attract the best talent.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                                    <small class="fw-bold" style="color: var(--primary-dark);">Profile Settings</small>
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
                                    <h5 class="alert-heading mb-1" style="color: var(--primary-dark);">Update Failed</h5>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Current Company Info -->
                    <div class="company-header">
                        <div class="company-avatar">
                            <?= strtoupper(substr($company['name'], 0, 2)) ?>
                        </div>
                        <div class="company-info">
                            <h4><?= htmlspecialchars($company['name']) ?></h4>
                            <p>Last updated: <?= !empty($company['updated_at']) ? date('M j, Y', strtotime($company['updated_at'])) : 'Never' ?></p>
                        </div>
                    </div>

                    <!-- Profile Form -->
                    <div class="profile-form-container">
                        <form method="POST" id="companyForm">

                            <!-- Basic Information Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-building"></i>
                                    Company Information
                                </h3>

                                <div class="form-group">
                                    <label class="form-label required-field">
                                        <i class="fas fa-signature"></i>
                                        Company Name
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-building"></i>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?= htmlspecialchars($company['name']) ?>" 
                                               placeholder="Enter your company name" 
                                               required 
                                               maxlength="100">
                                    </div>
                                    <div class="character-count" id="nameCount"><?= strlen($company['name']) ?>/100 characters</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-globe"></i>
                                                Website
                                            </label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-link"></i>
                                                <input type="text" name="website" class="form-control" 
                                                       value="<?= htmlspecialchars($company['website'] ?? '') ?>" 
                                                       placeholder="https://example.com" 
                                                       maxlength="255">
                                            </div>
                                            <div class="character-count" id="websiteCount"><?= strlen($company['website'] ?? '') ?>/255 characters</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-industry"></i>
                                                Industry
                                            </label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-briefcase"></i>
                                                <input type="text" name="industry" class="form-control" 
                                                       value="<?= htmlspecialchars($company['industry'] ?? '') ?>" 
                                                       placeholder="e.g. Technology, Healthcare, Finance" 
                                                       maxlength="100">
                                            </div>
                                            <div class="character-count" id="industryCount"><?= strlen($company['industry'] ?? '') ?>/100 characters</div>
                                            
                                            <!-- Industry Suggestions -->
                                            <div class="industry-suggestions">
                                                <small class="text-muted me-2">Quick select:</small>
                                                <span class="industry-tag" onclick="setIndustry('Technology')">Technology</span>
                                                <span class="industry-tag" onclick="setIndustry('Healthcare')">Healthcare</span>
                                                <span class="industry-tag" onclick="setIndustry('Finance')">Finance</span>
                                                <span class="industry-tag" onclick="setIndustry('Education')">Education</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Description Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-align-left"></i>
                                    Company Description
                                </h3>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-file-alt"></i>
                                        About Your Company
                                    </label>
                                    <textarea name="description" class="form-control" 
                                              placeholder="Describe your company's mission, culture, values, and what makes you unique..." 
                                              maxlength="1000"><?= htmlspecialchars($company['description']) ?></textarea>
                                    <div class="character-count" id="descriptionCount"><?= strlen($company['description']) ?>/1000 characters</div>
                                </div>
                            </div>

                            <!-- Form Tips -->
                            <div class="form-tips">
                                <h6>
                                    <i class="fas fa-lightbulb"></i>
                                    Profile Tips
                                </h6>
                                <ul>
                                    <li>Use a clear and professional company name</li>
                                    <li>Add your website to increase credibility</li>
                                    <li>Choose the right industry to attract relevant candidates</li>
                                    <li>Write a compelling description about your company culture</li>
                                    <li>Keep information up-to-date for the best results</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn submit-btn">
                                <i class="fas fa-save"></i>
                                Update Company Profile
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
        const nameInput = document.querySelector('input[name="name"]');
        const websiteInput = document.querySelector('input[name="website"]');
        const industryInput = document.querySelector('input[name="industry"]');
        const descriptionInput = document.querySelector('textarea[name="description"]');

        function updateCharacterCount(input, countElement) {
            countElement.textContent = `${input.value.length}/${input.maxLength} characters`;
        }

        // Initialize counts
        updateCharacterCount(nameInput, document.getElementById('nameCount'));
        updateCharacterCount(websiteInput, document.getElementById('websiteCount'));
        updateCharacterCount(industryInput, document.getElementById('industryCount'));
        updateCharacterCount(descriptionInput, document.getElementById('descriptionCount'));

        // Add event listeners
        nameInput.addEventListener('input', () => updateCharacterCount(nameInput, document.getElementById('nameCount')));
        websiteInput.addEventListener('input', () => updateCharacterCount(websiteInput, document.getElementById('websiteCount')));
        industryInput.addEventListener('input', () => updateCharacterCount(industryInput, document.getElementById('industryCount')));
        descriptionInput.addEventListener('input', () => updateCharacterCount(descriptionInput, document.getElementById('descriptionCount')));

        // Form submission enhancement
        const form = document.getElementById('companyForm');
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Profile...';
            submitBtn.disabled = true;
        });
    });

    // Industry quick select
    function setIndustry(industry) {
        document.querySelector('input[name="industry"]').value = industry;
        document.getElementById('industryCount').textContent = `${industry.length}/100 characters`;
    }
    </script>
    <?php include "../includes/footer.php"; ?>
</body>
</html>