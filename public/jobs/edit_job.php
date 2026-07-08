<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

/* Company-only access (adjust if you use a different session shape) */
if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}

/* Get job id from query */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid job id.");
}
$job_id = (int)$_GET['id'];

/* Fetch existing job */
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Job not found.");
}
$job = $result->fetch_assoc();

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title        = trim($_POST['title'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $job_type     = trim($_POST['job_type'] ?? '');
    $salary_range = trim($_POST['salary_range'] ?? '');

    if ($title === '' || $location === '' || $job_type === '' || $salary_range === '') {
        $error = "All fields are required.";
    } else {
        $u = $conn->prepare("
            UPDATE jobs
            SET title = ?, location = ?, job_type = ?, salary_range = ?
            WHERE id = ?
        ");
        if (!$u) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $u->bind_param("ssssi", $title, $location, $job_type, $salary_range, $job_id);
            if ($u->execute()) {
                header("Location: /job-portal/public/dashboard/company_dashboard.php?updated=1");
                exit;
            } else {
                $error = "Update failed: " . $u->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Job Portal</title>
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
            --card-shadow: 0 10px 30px rgba(26, 83, 92, 0.08);
            --hover-shadow: 0 20px 40px rgba(26, 83, 92, 0.15);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            color: #2d3748;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }

        .page-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(26, 83, 92, 0.05);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: var(--light-gradient);
            opacity: 0.05;
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .page-subtitle {
            color: #718096;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(26, 83, 92, 0.05);
            transition: var(--transition);
        }

        .form-container:hover {
            box-shadow: var(--hover-shadow);
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-group:last-of-type {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-light);
            font-size: 1rem;
        }

        .form-control,
        .form-select {
            border: 2px solid rgba(26, 83, 92, 0.08);
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f8fafc;
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-light);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(78, 205, 196, 0.1);
            outline: none;
        }

        .form-control:hover,
        .form-select:hover {
            border-color: var(--primary-light);
        }

        /* Alert Styles */
        .alert {
            border-radius: 12px;
            border: 1px solid;
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-danger {
            background-color: rgba(255, 107, 107, 0.08);
            color: #dc2626;
            border-color: rgba(255, 107, 107, 0.2);
        }

        .alert-success {
            background-color: rgba(78, 205, 196, 0.08);
            color: var(--primary-dark);
            border-color: rgba(78, 205, 196, 0.2);
        }

        .alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* Button Styles */
        .btn-group-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-update {
            background: var(--light-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            flex: 1;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(78, 205, 196, 0.3);
            color: white;
        }

        .btn-update:active {
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: transparent;
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            min-width: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-cancel:hover {
            background: var(--light-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            text-decoration: none;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, rgba(78, 205, 196, 0.05) 0%, rgba(26, 83, 92, 0.05) 100%);
            border-left: 4px solid var(--primary-light);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .info-box i {
            color: var(--primary-light);
            font-size: 1.25rem;
            margin-top: 0.25rem;
            flex-shrink: 0;
        }

        .info-box-text {
            font-size: 0.9rem;
            color: #2d3748;
            line-height: 1.5;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-container {
                padding: 15px;
            }

            .page-header,
            .form-container {
                padding: 1.75rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .btn-group-actions {
                flex-direction: column;
            }

            .btn-update,
            .btn-cancel {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="page-container">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <h1 class="page-title">
                <i class="fas fa-edit me-2"></i>Edit Job Listing
            </h1>
            <p class="page-subtitle">
                Update job details and information
            </p>
        </div>

        <!-- Form Container -->
        <div class="form-container fade-in">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div class="info-box-text">
                    <strong>Job ID:</strong> #<?= htmlspecialchars($job['id']) ?> | 
                    <strong>Status:</strong> <?= ucfirst($job['status']) ?> | 
                    <strong>Posted:</strong> <?= date('M j, Y', strtotime($job['created_at'])) ?>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-briefcase"></i>Job Title
                    </label>
                    <input type="text" name="title" class="form-control" required 
                           placeholder="e.g., Senior Software Engineer"
                           value="<?= htmlspecialchars($job['title']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt"></i>Location
                    </label>
                    <input type="text" name="location" class="form-control" required 
                           placeholder="e.g., New York, NY or Remote"
                           value="<?= htmlspecialchars($job['location']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock"></i>Job Type
                    </label>
                    <select name="job_type" class="form-select" required>
                        <option value="">Select Job Type</option>
                        <option value="Full Time" <?= ($job['job_type'] == 'Full Time') ? 'selected' : '' ?>>
                            <i class="fas fa-briefcase me-2"></i>Full Time
                        </option>
                        <option value="Part Time" <?= ($job['job_type'] == 'Part Time') ? 'selected' : '' ?>>
                            <i class="fas fa-hourglass-half me-2"></i>Part Time
                        </option>
                        <option value="Internship" <?= ($job['job_type'] == 'Internship') ? 'selected' : '' ?>>
                            <i class="fas fa-graduation-cap me-2"></i>Internship
                        </option>
                        <option value="Contract" <?= ($job['job_type'] == 'Contract') ? 'selected' : '' ?>>
                            <i class="fas fa-handshake me-2"></i>Contract
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-money-bill-wave"></i>Salary Range
                    </label>
                    <input type="text" name="salary_range" class="form-control" required 
                           placeholder="e.g., $60,000 - $100,000 PKR or Negotiable"
                           value="<?= htmlspecialchars($job['salary_range']) ?>">
                </div>

                <div class="btn-group-actions">
                    <button class="btn btn-update" type="submit">
                        <i class="fas fa-check-circle"></i>Update Job
                    </button>
                    <a href="my_jobs.php" class="btn btn-cancel">
                        <i class="fas fa-times-circle"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add fade-in animations
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 10);
            });
        });
    </script>
</body>
</html>
