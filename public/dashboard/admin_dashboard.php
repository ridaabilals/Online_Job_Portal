<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

/* ✅ COUNTS FOR DASHBOARD (safe) */
function q($sql) {
    global $conn;
    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        error_log("admin_dashboard SQL error: " . mysqli_error($conn) . " -- SQL: " . $sql);
        return false;
    }
    return $res;
}

function count_from($sql) {
    $res = q($sql);
    if ($res === false) return 0;
    $row = mysqli_fetch_assoc($res);
    return isset($row['total']) ? (int)$row['total'] : 0;
}

$userCount     = count_from("SELECT COUNT(*) AS total FROM users");
$companyCount  = count_from("SELECT COUNT(*) AS total FROM companies");
$jobCount      = count_from("SELECT COUNT(*) AS total FROM jobs");
$pendingCount  = count_from("SELECT COUNT(*) AS total FROM jobs WHERE status = 'pending'");
$approvedCount = count_from("SELECT COUNT(*) AS total FROM jobs WHERE status = 'approved'");
$rejectedCount = count_from("SELECT COUNT(*) AS total FROM jobs WHERE status = 'rejected'");

// Get recent activities
$recentActivitiesSql = "
    SELECT 'user' as type, CONCAT('New user registered: ', username) as activity, created_at as date
    FROM users
    UNION ALL
    SELECT 'job' as type, CONCAT('New job posted: ', title) as activity, created_at as date
    FROM jobs
    UNION ALL
    SELECT 'company' as type, CONCAT('New company registered: ', name) as activity, created_at as date
    FROM companies
    ORDER BY date DESC
    LIMIT 5
";
$recentActivities = q($recentActivitiesSql);

// Get daily stats
$today = date('Y-m-d');
$todayUsers = count_from("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = '$today'");
$todayJobs  = count_from("SELECT COUNT(*) as total FROM jobs WHERE DATE(created_at) = '$today'");

include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Job Portal</title>
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
            --gradient: linear-gradient(135deg, #1A535C 0%, #2a7a85 100%);
            --light-gradient: linear-gradient(135deg, #4ECDC4 0%, #6bd4cc 100%);
            --accent-gradient: linear-gradient(135deg, #FF6B6B 0%, #ff5252 100%);
            --highlight-gradient: linear-gradient(135deg, #FFE66D 0%, #ffed80 100%);
            --purple-gradient: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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

        /* Dashboard Layout */
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

        .admin-avatar {
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

        /* Main Stats Grid */
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

        .stat-card.users::before { background: var(--gradient); }
        .stat-card.companies::before { background: var(--light-gradient); }
        .stat-card.jobs::before { background: var(--purple-gradient); }
        .stat-card.pending::before { background: var(--highlight-gradient); }
        .stat-card.approved::before { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
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

        .stat-icon.users { background: var(--gradient); }
        .stat-icon.companies { background: var(--light-gradient); }
        .stat-icon.jobs { background: var(--purple-gradient); }
        .stat-icon.pending { background: var(--highlight-gradient); }
        .stat-icon.approved { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.rejected { background: var(--accent-gradient); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin: 0.5rem 0;
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Action Grid */
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

        /* Special Action Cards */
        .urgent-card {
            border-top: 4px solid var(--highlight) !important;
            position: relative;
            overflow: hidden;
        }

        .urgent-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: var(--highlight-gradient);
            border-radius: 0 20px 0 100%;
        }

        .rejected-card {
            border-top: 4px solid var(--accent) !important;
            position: relative;
            overflow: hidden;
        }

        .rejected-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: var(--accent-gradient);
            border-radius: 0 20px 0 100%;
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

        /* Buttons */
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

        .btn-warning-gradient {
            background: var(--highlight-gradient);
            color: #333;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-warning-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 230, 109, 0.3);
            color: #333;
        }

        .btn-danger-gradient {
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-danger-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
            color: white;
        }

        /* Platform Summary */
        .platform-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            text-align: center;
        }

        .summary-item {
            padding: 1rem;
            border-radius: 12px;
            background: #f8fafc;
            transition: var(--transition);
        }

        .summary-item:hover {
            transform: translateY(-5px);
            background: white;
            box-shadow: var(--card-shadow);
        }

        .summary-item .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .summary-item.users .value { color: var(--primary-dark); }
        .summary-item.companies .value { color: var(--primary-light); }
        .summary-item.pending .value { color: #d97706; }
        .summary-item.approved .value { color: #059669; }
        .summary-item.rejected .value { color: var(--accent); }

        /* Recent Activity */
        .activity-feed {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
        }

        .activity-item {
            padding: 1rem;
            border-left: 3px solid var(--primary-light);
            margin-bottom: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: var(--transition);
        }

        .activity-item:hover {
            transform: translateX(5px);
            background: white;
            box-shadow: var(--card-shadow);
        }

        .activity-item.user { border-left-color: var(--primary-dark); }
        .activity-item.job { border-left-color: var(--purple-gradient); }
        .activity-item.company { border-left-color: var(--light-gradient); }

        /* Today's Stats */
        .today-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .today-stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            text-align: center;
            border-top: 4px solid var(--primary-light);
        }

        .today-stat-card:nth-child(2) {
            border-top-color: var(--highlight-gradient);
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

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
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

            .admin-avatar {
                margin: 0 auto 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-value {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--accent-gradient);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header fade-in">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="admin-avatar pulse">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="display-6 fw-bold mb-2" style="color: var(--primary-dark);">
                                Admin Control Panel
                            </h1>
                            <p class="lead mb-0 text-muted">
                                <i class="fas fa-chart-line me-2"></i>
                                Manage and monitor the entire platform
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
                    
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="badge bg-success me-2 mb-2">
                                    <i class="fas fa-users me-1"></i>
                                    <?= $userCount ?> Users
                                </span>
                                <span class="badge bg-primary me-2 mb-2">
                                    <i class="fas fa-building me-1"></i>
                                    <?= $companyCount ?> Companies
                                </span>
                                <span class="badge bg-warning text-dark mb-2">
                                    <i class="fas fa-briefcase me-1"></i>
                                    <?= $jobCount ?> Jobs
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Stats -->
        <div class="mb-4">
            <h3 class="section-title">Today's Overview</h3>
            <div class="today-stats">
                <div class="today-stat-card fade-in" style="animation-delay: 0.1s">
                    <h6 class="text-muted mb-2">New Users Today</h6>
                    <div class="stat-value"><?= $todayUsers ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= min($todayUsers * 10, 100) ?>%; background: var(--light-gradient);"></div>
                    </div>
                </div>
                
                <div class="today-stat-card fade-in" style="animation-delay: 0.2s">
                    <h6 class="text-muted mb-2">New Jobs Today</h6>
                    <div class="stat-value"><?= $todayJobs ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= min($todayJobs * 20, 100) ?>%; background: var(--highlight-gradient);"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform Statistics -->
        <div class="mb-4">
            <h3 class="section-title">Platform Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card users fade-in" style="animation-delay: 0.1s">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <h6 class="text-muted mb-2">Total Users</h6>
                    <div class="stat-value"><?= $userCount ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: 100%; background: var(--gradient);"></div>
                    </div>
                </div>

                <div class="stat-card companies fade-in" style="animation-delay: 0.2s">
                    <div class="stat-icon companies">
                        <i class="fas fa-building"></i>
                    </div>
                    <h6 class="text-muted mb-2">Companies</h6>
                    <div class="stat-value"><?= $companyCount ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: 100%; background: var(--light-gradient);"></div>
                    </div>
                </div>

                <div class="stat-card jobs fade-in" style="animation-delay: 0.3s">
                    <div class="stat-icon jobs">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h6 class="text-muted mb-2">Total Jobs</h6>
                    <div class="stat-value"><?= $jobCount ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: 100%; background: var(--purple-gradient);"></div>
                    </div>
                </div>

                <div class="stat-card pending fade-in" style="animation-delay: 0.4s">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h6 class="text-muted mb-2">Pending Jobs</h6>
                    <div class="stat-value"><?= $pendingCount ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $jobCount > 0 ? ($pendingCount/$jobCount)*100 : 0 ?>%; background: var(--highlight-gradient);"></div>
                    </div>
                </div>

                <div class="stat-card approved fade-in" style="animation-delay: 0.5s">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h6 class="text-muted mb-2">Approved Jobs</h6>
                    <div class="stat-value"><?= $approvedCount ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $jobCount > 0 ? ($approvedCount/$jobCount)*100 : 0 ?>%; background: linear-gradient(135deg, #10b981 0%, #059669 100%);"></div>
                    </div>
                </div>

                <div class="stat-card rejected fade-in" style="animation-delay: 0.6s">
                    <div class="stat-icon rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h6 class="text-muted mb-2">Rejected Jobs</h6>
                    <div class="stat-value"><?= $rejectedCount ?></div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $jobCount > 0 ? ($rejectedCount/$jobCount)*100 : 0 ?>%; background: var(--accent-gradient);"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Management -->
        <div class="mb-4">
            <h3 class="section-title">Quick Management</h3>
            <div class="action-grid">
                <div class="action-card fade-in" style="animation-delay: 0.1s">
                    <div class="action-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h5 class="mb-2">Manage Users</h5>
                    <p class="text-muted mb-3">Manage user accounts, permissions, and roles</p>
                    <a href="/job-portal/public/admin/manage_users.php" class="btn btn-gradient w-100">
                        <i class="fas fa-cog me-2"></i>Manage Users
                    </a>
                </div>

                <div class="action-card fade-in" style="animation-delay: 0.2s">
                    <div class="action-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h5 class="mb-2">Manage Companies</h5>
                    <p class="text-muted mb-3">Manage company profiles and verify listings</p>
                    <a href="/job-portal/public/admin/manage_companies.php" class="btn btn-outline-gradient w-100">
                        <i class="fas fa-cog me-2"></i>Manage Companies
                    </a>
                </div>

                <div class="action-card fade-in" style="animation-delay: 0.3s">
                    <div class="action-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h5 class="mb-2">Manage All Jobs</h5>
                    <p class="text-muted mb-3">Manage and moderate all job postings</p>
                    <a href="/job-portal/public/admin/manage_jobs.php" class="btn btn-gradient w-100">
                        <i class="fas fa-cog me-2"></i>Manage Jobs
                    </a>
                </div>
            </div>
        </div>

        <!-- Job Moderation -->
        <div class="mb-4">
            <h3 class="section-title">Job Moderation</h3>
            <div class="action-grid">
                <div class="action-card urgent-card fade-in" style="animation-delay: 0.1s">
                    <div class="position-relative">
                        <div class="action-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <?php if($pendingCount > 0): ?>
                        <span class="notification-badge pulse"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-2">Pending Approvals</h5>
                    <p class="text-muted mb-3"><?= $pendingCount ?> jobs require immediate review</p>
                    <a href="/job-portal/public/admin/pending_jobs.php" class="btn btn-warning-gradient w-100">
                        <i class="fas fa-clipboard-check me-2"></i>Review Now
                    </a>
                </div>

                <div class="action-card rejected-card fade-in" style="animation-delay: 0.2s">
                    <div class="action-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <h5 class="mb-2">Rejected Jobs</h5>
                    <p class="text-muted mb-3"><?= $rejectedCount ?> jobs were rejected</p>
                    <a href="/job-portal/public/admin/rejected_jobs.php" class="btn btn-danger-gradient w-100">
                        <i class="fas fa-eye me-2"></i>View Rejected
                    </a>
                </div>
            </div>
        </div>

        <!-- Platform Summary -->
        <div class="mb-4">
            <h3 class="section-title">Platform Summary</h3>
            <div class="platform-summary fade-in">
                <div class="summary-grid">
                    <div class="summary-item users">
                        <div class="value"><?= $userCount ?></div>
                        <div class="text-muted">Total Users</div>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?= $todayUsers ?> today
                        </small>
                    </div>
                    
                    <div class="summary-item companies">
                        <div class="value"><?= $companyCount ?></div>
                        <div class="text-muted">Companies</div>
                        <small class="text-primary">Registered</small>
                    </div>
                    
                    <div class="summary-item">
                        <div class="value" style="color: var(--purple-gradient);"><?= $jobCount ?></div>
                        <div class="text-muted">Total Jobs</div>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?= $todayJobs ?> today
                        </small>
                    </div>
                    
                    <div class="summary-item pending">
                        <div class="value"><?= $pendingCount ?></div>
                        <div class="text-muted">Pending</div>
                        <small class="text-warning">Requires action</small>
                    </div>
                    
                    <div class="summary-item approved">
                        <div class="value"><?= $approvedCount ?></div>
                        <div class="text-muted">Approved</div>
                        <small class="text-success">Active jobs</small>
                    </div>
                    
                    <div class="summary-item rejected">
                        <div class="value"><?= $rejectedCount ?></div>
                        <div class="text-muted">Rejected</div>
                        <small class="text-danger">Not approved</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mb-4">
            <h3 class="section-title">Recent Activity</h3>
            <div class="activity-feed fade-in">
                <?php 
                    $hasRecent = ($recentActivities && mysqli_num_rows($recentActivities) > 0);
                    if ($hasRecent):
                        while ($activity = mysqli_fetch_assoc($recentActivities)):
                ?>
                            <div class="activity-item <?= htmlspecialchars($activity['type']) ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1"><?= htmlspecialchars($activity['activity']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('h:i A', strtotime($activity['date'])) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        <?= ucfirst(htmlspecialchars($activity['type'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No recent activity</h5>
                        </div>
                    <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    
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
            
            // Add hover effects to all cards
            const cards = document.querySelectorAll('.stat-card, .action-card, .today-stat-card, .summary-item');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>