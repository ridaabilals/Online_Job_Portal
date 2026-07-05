<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ✅ GET REAL COMPANY ID */
$stmt = $conn->prepare("SELECT id, name, description FROM companies WHERE user_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    // ✅ AUTO-CREATE COMPANY PROFILE
    $insert = $conn->prepare("
        INSERT INTO companies (user_id, name, description, created_at)
        VALUES (?, ?, '', NOW())
    ");
    if ($insert === false) {
        die("Insert prepare failed: " . htmlspecialchars($conn->error));
    }
    $companyName = $_SESSION['user']['full_name'] ?? $_SESSION['username'] ?? $_SESSION['user']['username'] ?? ('Company ' . $user_id);
    $insert->bind_param("is", $user_id, $companyName);
    $insert->execute();

    // ✅ RE-FETCH COMPANY
    $stmt = $conn->prepare("SELECT id, name, description FROM companies WHERE user_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
}

$company = $res->fetch_assoc();
$company_id = $company['id'];

/* ✅ FETCH ONLY THIS COMPANY JOBS */
$sql = "SELECT id, title, location, job_type, salary_range, status 
        FROM jobs 
        WHERE company_id = ? 
        ORDER BY id DESC 
        LIMIT 6";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Jobs prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();

// Add application count to each job
$jobsArray = [];
if ($result && $result->num_rows > 0) {
    while ($job = $result->fetch_assoc()) {
        $appCount = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT COUNT(*) as count FROM applications WHERE job_id = {$job['id']}"
        ))['count'] ?? 0;
        $job['application_count'] = $appCount;
        $jobsArray[] = $job;
    }
}

/* ✅ GET COMPANY STATS */
$stats = [];
$stats['totalJobs'] = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM jobs WHERE company_id = $company_id"
))['count'] ?? 0;
$stats['pendingJobs'] = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM jobs WHERE company_id = $company_id AND status = 'pending'"
))['count'] ?? 0;
$stats['approvedJobs'] = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM jobs WHERE company_id = $company_id AND status = 'approved'"
))['count'] ?? 0;
$stats['rejectedJobs'] = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM jobs WHERE company_id = $company_id AND status = 'rejected'"
))['count'] ?? 0;

// Get total applications for this company's jobs
$stats['totalApplications'] = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM applications a 
     JOIN jobs j ON a.job_id = j.id 
     WHERE j.company_id = $company_id"
))['count'] ?? 0;

// Get recent applications
$recentAppsStmt = $conn->prepare("
    SELECT a.*, u.username, u.email, j.title as job_title, j.id as job_id
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN jobs j ON a.job_id = j.id
    WHERE j.company_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
if ($recentAppsStmt === false) {
    $recentAppsResult = mysqli_query($conn, "SELECT NULL LIMIT 0"); // Empty result
} else {
    $recentAppsStmt->bind_param("i", $company_id);
    $recentAppsStmt->execute();
    $recentAppsResult = $recentAppsStmt->get_result();
}
// Safely compute count
$recentAppsCount = (is_object($recentAppsResult) && isset($recentAppsResult->num_rows)) ? $recentAppsResult->num_rows : 0;

// Get hired applicants
$hiredStmt = $conn->prepare("
    SELECT a.id, a.applied_at, u.username, u.email, j.title as job_title
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN jobs j ON a.job_id = j.id
    WHERE j.company_id = ? AND a.status = 'hired'
    ORDER BY a.applied_at DESC
    LIMIT 5
");
if ($hiredStmt === false) {
    $hiredResult = mysqli_query($conn, "SELECT NULL LIMIT 0"); // Empty result
    $stats['totalHired'] = 0;
} else {
    $hiredStmt->bind_param("i", $company_id);
    $hiredStmt->execute();
    $hiredResult = $hiredStmt->get_result();
    $stats['totalHired'] = is_object($hiredResult) && isset($hiredResult->num_rows) ? $hiredResult->num_rows : 0;
}

/* Resolve job view URL (pick the first matching file on disk) */
$publicDir = realpath(__DIR__ . '/../'); // c:\xampp\htdocs\job-portal\public
$jobViewCandidates = [
    $publicDir . '/job_view.php' => '../job_view.php',
    $publicDir . '/jobs/view_job.php' => '../jobs/view_job.php',
    $publicDir . '/jobs/view.php' => '../jobs/view.php',
    $publicDir . '/jobs/viewjob.php' => '../jobs/viewjob.php'
];
$job_view_link_base = '../jobs/view_job.php'; // default relative
foreach ($jobViewCandidates as $fs => $web) {
    if (file_exists($fs)) {
        $job_view_link_base = $web;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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

        /* Custom Scrollbar */
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

        /* Layout */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .dashboard-header {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(26, 83, 92, 0.05);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
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

        .company-avatar {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin-right: 1.5rem;
            box-shadow: 0 10px 20px rgba(26, 83, 92, 0.2);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(26, 83, 92, 0.05);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--hover-shadow);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
            border-radius: 20px 20px 0 0;
        }

        .stat-card.pending::before { background: var(--highlight-gradient); }
        .stat-card.approved::before { background: var(--light-gradient); }
        .stat-card.rejected::before { background: var(--accent-gradient); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.total { background: var(--gradient); }
        .stat-icon.pending { background: var(--highlight-gradient); }
        .stat-icon.approved { background: var(--light-gradient); }
        .stat-icon.rejected { background: var(--accent-gradient); }
        .stat-icon.applications { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin: 0.5rem 0;
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card.pending .stat-value,
        .stat-card.approved .stat-value,
        .stat-card.rejected .stat-value {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(26, 83, 92, 0.05);
            position: relative;
            overflow: hidden;
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }

        .action-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--light-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .action-card:hover::after {
            transform: scaleX(1);
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        /* Job Cards */
        .job-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .job-card {
            background: white;
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(26, 83, 92, 0.05);
            position: relative;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .job-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
            background: var(--primary-dark);
        }

        .job-card.pending::before { background: var(--highlight); }
        .job-card.approved::before { background: var(--primary-light); }
        .job-card.rejected::before { background: var(--accent); }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: rgba(255, 230, 109, 0.15);
            color: #d97706;
            border: 1px solid rgba(255, 230, 109, 0.3);
        }

        .badge-approved {
            background: rgba(78, 205, 196, 0.15);
            color: var(--primary-dark);
            border: 1px solid rgba(78, 205, 196, 0.3);
        }

        .badge-rejected {
            background: rgba(255, 107, 107, 0.15);
            color: #dc2626;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        /* Applications Table */
        .applications-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
        }

        .table-header {
            background: var(--gradient);
            color: white;
            padding: 1.5rem;
        }

        .table-row {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s ease;
        }

        .table-row:hover {
            background-color: #f8fafc;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 2px dashed #e2e8f0;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
            color: var(--primary-dark);
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

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            .dashboard-header {
                padding: 1.5rem;
                text-align: center;
            }

            .company-avatar {
                margin: 0 auto 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .job-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Custom Buttons */
        .btn-gradient {
            background: var(--light-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(78, 205, 196, 0.3);
            color: white;
        }

        .btn-outline-gradient {
            background: transparent;
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-outline-gradient:hover {
            background: var(--light-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        /* Section Headers */
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }

        /* Progress Bars */
        .progress-container {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            background: var(--gradient);
            border-radius: 4px;
            transition: width 1s ease;
        }
    </style>
</head>
<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header fade-in">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="company-avatar pulse">
                        <?= strtoupper(substr($company['name'], 0, 1)) ?>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="display-6 fw-bold mb-2" style="color: var(--primary-dark);">
                                <?= htmlspecialchars($company['name']) ?>
                            </h1>
                            <p class="lead mb-0 text-muted">
                                <i class="fas fa-chart-line me-2"></i>
                                Company Dashboard
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-dark px-3 py-2 mb-2">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?= date('F j, Y') ?>
                            </div>
                            <p class="mb-0 text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                <?= date('h:i A') ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($company['description'])): ?>
                    <div class="mt-3">
                        <p class="mb-0"><?= htmlspecialchars($company['description']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-4">
            <h3 class="section-title">Quick Actions</h3>
            <div class="action-grid">
                <div class="action-card fade-in" style="animation-delay: 0.1s">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h5 class="mb-2">Post New Job</h5>
                    <p class="text-muted mb-3">Create a new job listing to attract talent</p>
                    <a href="../jobs/add_job.php" class="btn btn-gradient w-100">
                        <i class="fas fa-plus me-2"></i>Create Job
                    </a>
                </div>

                <div class="action-card fade-in" style="animation-delay: 0.2s">
                    <div class="action-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h5 class="mb-2">Company Profile</h5>
                    <p class="text-muted mb-3">Update your company information</p>
                    <a href="../company/edit_company.php" class="btn btn-outline-gradient w-100">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </a>
                </div>

                <div class="action-card fade-in" style="animation-delay: 0.3s">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5 class="mb-2">View Applicants</h5>
                    <p class="text-muted mb-3">Manage and review job applications</p>
                    <a href="/job-portal/public/applications/my_applications.php" class="btn btn-gradient w-100">
                        <i class="fas fa-eye me-2"></i>View All
                    </a>
                </div>

                <div class="action-card fade-in" style="animation-delay: 0.4s">
                    <div class="action-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h5 class="mb-2">Hired Candidates</h5>
                    <p class="text-muted mb-3">View successfully hired applicants</p>
                    <button type="button" class="btn btn-outline-gradient w-100" data-bs-toggle="modal" data-bs-target="#hiredModal">
                        <i class="fas fa-star me-2"></i>View (<?= $stats['totalHired'] ?>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="mb-4">
            <h3 class="section-title">Statistics Overview</h3>
            <div class="stats-grid">
                <div class="stat-card fade-in" style="animation-delay: 0.1s">
                    <div class="stat-icon total">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h6 class="text-muted mb-2">Total Jobs</h6>
                    <div class="stat-value"><?= $stats['totalJobs'] ?></div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                </div>

                <div class="stat-card pending fade-in" style="animation-delay: 0.2s">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h6 class="text-muted mb-2">Pending Approval</h6>
                    <div class="stat-value"><?= $stats['pendingJobs'] ?></div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $stats['totalJobs'] > 0 ? ($stats['pendingJobs']/$stats['totalJobs'])*100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="stat-card approved fade-in" style="animation-delay: 0.3s">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h6 class="text-muted mb-2">Approved Jobs</h6>
                    <div class="stat-value"><?= $stats['approvedJobs'] ?></div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $stats['totalJobs'] > 0 ? ($stats['approvedJobs']/$stats['totalJobs'])*100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="stat-card rejected fade-in" style="animation-delay: 0.4s">
                    <div class="stat-icon rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h6 class="text-muted mb-2">Rejected Jobs</h6>
                    <div class="stat-value"><?= $stats['rejectedJobs'] ?></div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $stats['totalJobs'] > 0 ? ($stats['rejectedJobs']/$stats['totalJobs'])*100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="stat-card fade-in" style="animation-delay: 0.5s">
                    <div class="stat-icon applications">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h6 class="text-muted mb-2">Applications</h6>
                    <div class="stat-value"><?= $stats['totalApplications'] ?></div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $stats['totalJobs'] > 0 ? min(($stats['totalApplications']/($stats['totalJobs']*10))*100, 100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Job Postings -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-title mb-0">Recent Job Postings</h3>
                <a href="../jobs/view_jobs.php" class="btn btn-outline-gradient">
                    <i class="fas fa-list me-2"></i>View All
                </a>
            </div>

            <?php if (count($jobsArray) > 0): ?>
                <div class="job-grid">
                    <?php foreach ($jobsArray as $job): ?>
                        <div class="job-card <?= $job['status'] ?> fade-in">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">
                                    <?= htmlspecialchars($job['title']) ?>
                                </h5>
                                <span class="status-badge badge-<?= $job['status'] ?>">
                                    <?= ucfirst($job['status']) ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-muted mb-2" style="font-size: 0.9rem;">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?= htmlspecialchars($job['location']) ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-clock me-2"></i>
                                    <?= htmlspecialchars($job['job_type']) ?>
                                </p>
                                <p class="mb-2" style="color: var(--primary-dark); font-weight: 600;">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    <?= htmlspecialchars($job['salary_range']) ?>
                                </p>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-users me-2"></i>
                                    <span><?= $job['application_count'] ?> applications</span>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="../jobs/edit_job.php?id=<?= (int)$job['id'] ?>" 
                                   class="btn btn-outline-gradient flex-fill">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a>
                                <!-- resolved job view link (computed above) -->
                                <a href="<?= htmlspecialchars($job_view_link_base . '?id=' . (int)$job['id']) ?>" 
                                   class="btn btn-gradient flex-fill">
                                    <i class="fas fa-eye me-2"></i>View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state fade-in">
                    <div class="empty-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h4 class="mb-3" style="color: var(--primary-dark);">No Jobs Posted Yet</h4>
                    <p class="text-muted mb-4">Start attracting talent by posting your first job listing</p>
                    <a href="../jobs/add_job.php" class="btn btn-gradient px-4">
                        <i class="fas fa-plus me-2"></i>Post Your First Job
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Applications -->
        <?php if ($recentAppsCount > 0): ?>
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-title mb-0">Recent Applications</h3>
                <a href="/job-portal/public/applications/my_applications.php" class="btn btn-outline-gradient">
                    <i class="fas fa-external-link-alt me-2"></i>Manage All
                </a>
            </div>
            
            <div class="applications-table fade-in">
                <div class="table-header">
                    <div class="row">
                        <div class="col-md-3"><strong>Applicant</strong></div>
                        <div class="col-md-4"><strong>Job Position</strong></div>
                        <div class="col-md-3"><strong>Applied Date</strong></div>
                        <div class="col-md-2"><strong>Status</strong></div>
                    </div>
                </div>
                
                <?php if (is_object($recentAppsResult)): ?>
                    <?php while ($application = $recentAppsResult->fetch_assoc()): ?>
                <div class="table-row">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" 
                                     style="width: 36px; height: 36px;">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($application['username']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($application['email']) ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <?= ucfirst($application['job_title']) ?>
                        </div>
                        <div class="col-md-3">
                            <?= date('M j, Y', strtotime($application['applied_at'])) ?>
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-light text-dark px-3 py-1">
                                <?= ucfirst($application['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                    <?php endwhile; ?>
                <?php endif; ?>
             </div>
         </div>
         <?php endif; ?>
    </div>

    <!-- Hired Candidates Modal -->
    <div class="modal fade" id="hiredModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check me-2"></i>Hired Candidates
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($stats['totalHired'])): ?>
                        <?php if (is_object($hiredResult)): ?>
                            <?php while ($hired = $hiredResult->fetch_assoc()): ?>
                             <div class="card mb-3 border-success">
                                 <div class="card-body">
                                     <div class="row align-items-center">
                                         <div class="col-md-8">
                                             <h6 class="card-title mb-1">
                                                 <?= htmlspecialchars($hired['username']) ?>
                                             </h6>
                                             <p class="card-text text-muted mb-1">
                                                 <i class="fas fa-envelope me-1"></i>
                                                 <?= htmlspecialchars($hired['email']) ?>
                                             </p>
                                             <p class="card-text mb-0">
                                                 <small class="text-success">
                                                     <i class="fas fa-briefcase me-1"></i>
                                                     <?= htmlspecialchars($hired['job_title']) ?>
                                                 </small>
                                             </p>
                                         </div>
                                         <div class="col-md-4 text-end">
                                             <span class="badge bg-success">
                                                 <i class="fas fa-calendar-check me-1"></i>
                                                 Hired on <?= date('M j, Y', strtotime($hired['applied_at'])) ?>
                                             </span>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- no hired result object -->
                        <?php endif; ?>
                    <?php else: ?>
                         <div class="text-center py-4">
                             <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                             <h5 class="text-muted">No hired candidates yet</h5>
                             <p class="text-muted">Candidates you hire will appear here</p>
                         </div>
                     <?php endif; ?>
                 </div>
             </div>
         </div>
     </div>

    <?php include "../includes/footer.php"; ?>

    
    <script>
        // Add animations on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            fadeElements.forEach(el => {
                el.style.opacity = 0;
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
            
            // Update time every minute
            function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: true 
                });
                const timeElements = document.querySelectorAll('.time-update');
                timeElements.forEach(el => {
                    el.textContent = timeString;
                });
            }
            
            setInterval(updateTime, 60000);
        });
    </script>
</body>
</html>