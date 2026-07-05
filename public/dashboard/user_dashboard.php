<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

/* ===========================
   ONLY JOB SEEKER ALLOWED
   =========================== */
if (!isset($_SESSION['user_id']) || ($_SESSION['is_company'] ?? 1) != 0) {
    header("Location: /job-portal/public/login.php");
    exit();
}

/* Logged-in User ID */
$uid = (int) $_SESSION['user_id'];

/* Get Applications */
$appsStmt = $conn->prepare("
    SELECT a.*, j.title, c.name AS company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.id DESC
");
if ($appsStmt) {
    $appsStmt->bind_param("i", $uid);
    $appsStmt->execute();
    $apps = $appsStmt->get_result();
} else {
    $fallbackSql = "SELECT a.*, j.title, c.name AS company_name
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            JOIN companies c ON j.company_id = c.id
            WHERE a.user_id = " . (int)$uid . "
            ORDER BY a.id DESC";
    $apps = $conn->query($fallbackSql);
}

// Fetch notifications
$notifStmt = $conn->prepare("SELECT id, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
if ($notifStmt) {
    $notifStmt->bind_param("i", $uid);
    $notifStmt->execute();
    $notifications = $notifStmt->get_result();
} else {
    $notifications = false;
}

/* DASHBOARD COUNTS */
$countQuery = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END)       AS pending,
        SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END)   AS shortlisted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END)      AS rejected
    FROM applications
    WHERE user_id = ?
");
$countQuery->bind_param("i", $uid);
$countQuery->execute();
$countsResult = $countQuery->get_result();
$counts = $countsResult ? $countsResult->fetch_assoc() : null;

/* Default values if DB returned nothing */
$total       = isset($counts['total'])       ? (int)$counts['total']       : 0;
$pending     = isset($counts['pending'])     ? (int)$counts['pending']     : 0;
$shortlisted = isset($counts['shortlisted']) ? (int)$counts['shortlisted'] : 0;
$rejected    = isset($counts['rejected'])    ? (int)$counts['rejected']    : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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

    .dashboard-container {
        background: var(--background);
        min-height: 100vh;
        padding-top: 80px;
    }
    .main-content {
        margin-left: 0;
        padding: 30px;
    }

    .welcome-section {
        background: var(--gradient);
        color: white;
        border-radius: 20px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
        position: relative;
        overflow: hidden;
    }

    .welcome-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: rgba(78, 205, 196, 0.2);
        border-radius: 50%;
    }

    .welcome-section h2 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        font-size: 2.2rem;
    }

    .welcome-section .lead {
        font-size: 1.2rem;
        opacity: 0.9;
    }

    .stats-card {
        border: none;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient);
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .stats-card.total { 
        background: var(--background);
        border: 2px solid var(--primary-dark);
    }
    .stats-card.pending { 
        background: var(--background);
        border: 2px solid var(--highlight);
    }
    .stats-card.shortlisted { 
        background: var(--background);
        border: 2px solid var(--primary-light);
    }
    .stats-card.rejected { 
        background: var(--background);
        border: 2px solid var(--accent);
    }

    .stats-card .card-body {
        color: var(--primary-dark);
        text-align: center;
        padding: 1.5rem 1rem;
    }

    .stats-card h6 {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-bottom: 0.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stats-card h2 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0;
        background: var(--gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .application-card {
        border: none;
        border-radius: 15px;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary-dark);
        background: var(--background);
    }

    .application-card:hover {
        transform: translateX(5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .application-card.pending { border-left-color: var(--highlight); }
    .application-card.shortlisted { border-left-color: var(--primary-light); }
    .application-card.rejected { border-left-color: var(--accent); }

    .quick-action-card {
        background: var(--background);
        border: 2px solid rgba(26, 83, 92, 0.1);
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .quick-action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--light-gradient);
    }

    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        border-color: var(--primary-light);
    }

    .quick-action-card i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        background: var(--gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.85rem;
        border: 2px solid transparent;
    }

    .badge-pending { 
        background: rgba(255, 230, 109, 0.2); 
        color: var(--primary-dark);
        border-color: var(--highlight);
    }
    .badge-shortlisted { 
        background: rgba(78, 205, 196, 0.2); 
        color: var(--primary-dark);
        border-color: var(--primary-light);
    }
    .badge-rejected { 
        background: rgba(255, 107, 107, 0.2); 
        color: var(--primary-dark);
        border-color: var(--accent);
    }

    .notification-toast {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        margin-bottom: 1rem;
        border-left: 4px solid var(--primary-light);
        background: var(--background);
    }

    .section-title {
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        position: relative;
        padding-bottom: 0.5rem;
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

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--primary-dark);
        background: var(--background);
        border-radius: 15px;
        border: 2px dashed rgba(26, 83, 92, 0.2);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
        color: var(--primary-dark);
    }

    .gradient-text {
        background: var(--gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .btn-primary {
        background: var(--gradient);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(26, 83, 92, 0.3);
        background: var(--gradient);
    }

    .btn-outline-primary {
        border: 2px solid var(--primary-dark);
        color: var(--primary-dark);
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(26, 83, 92, 0.2);
    }

    .view-details-btn {
        background: var(--gradient);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .view-details-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(26, 83, 92, 0.3);
        color: white;
    }

    .progress {
        height: 8px;
        border-radius: 10px;
        background: rgba(26, 83, 92, 0.1);
    }

    .progress-bar {
        border-radius: 10px;
    }

    .bg-success { background: var(--primary-light) !important; }
    .bg-warning { background: var(--highlight) !important; }
    .bg-danger { background: var(--accent) !important; }

    .text-success { color: var(--primary-light) !important; }
    .text-warning { color: var(--highlight) !important; }
    .text-danger { color: var(--accent) !important; }

    .badge.bg-primary {
        background: var(--gradient) !important;
        border: none;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        background: var(--background);
    }

    .alert-success {
        background: rgba(78, 205, 196, 0.1);
        border: none;
        border-left: 4px solid var(--primary-light);
        color: var(--primary-dark);
        border-radius: 12px;
    }

    .alert-info {
        background: rgba(255, 230, 109, 0.1);
        border: none;
        border-left: 4px solid var(--highlight);
        color: var(--primary-dark);
        border-radius: 12px;
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 15px;
        }
        
        .welcome-section {
            padding: 1.5rem;
            text-align: center;
        }
        
        .welcome-section h2 {
            font-size: 1.8rem;
        }
        
        .stats-card h2 {
            font-size: 2rem;
        }
    }

    .floating-element {
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .stats-card i {
        font-size: 2rem;
        margin-bottom: 1rem;
        opacity: 0.8;
    }

    .stats-card.total i { color: var(--primary-dark); }
    .stats-card.pending i { color: var(--highlight); }
    .stats-card.shortlisted i { color: var(--primary-light); }
    .stats-card.rejected i { color: var(--accent); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="dashboard-container">
    <div class="container-fluid">
        <div class="row">
            <!-- MAIN CONTENT -->
            <div class="col-12 main-content">

                <!-- SUCCESS MESSAGE -->
                <?php if (!empty($_GET['applied'])): ?>
                    <div class="alert alert-success alert-dismissible fade show notification-toast" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2" style="color: var(--primary-light);"></i>
                            <strong>Success!</strong> Application submitted successfully.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- NOTIFICATIONS -->
                <?php if ($notifications && $notifications->num_rows > 0): ?>
                    <?php while ($n = $notifications->fetch_assoc()): ?>
                        <div class="alert alert-info alert-dismissible fade show notification-toast" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-bell me-2 mt-1" style="color: var(--highlight);"></i>
                                <div class="flex-grow-1">
                                    <strong>Update:</strong> <?= htmlspecialchars($n['message']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($n['created_at']) ?></small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <a href="/job-portal/public/notifications/mark_read.php?id=<?= (int)$n['id'] ?>&redirect=/job-portal/public/dashboard/user_dashboard.php" class="btn btn-sm btn-outline-primary">Mark as read</a>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>

                <!-- WELCOME SECTION -->
                <div class="welcome-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>👋 Welcome to Your Dashboard</h2>
                            <p class="lead">Track your job applications and manage your career journey in one place.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                                <small class="text-primary fw-bold" style="color: var(--primary-dark) !important;"><?= date('l, F j, Y') ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RECOMMENDED JOBS -->
                <div id="recommended" class="mb-5">
                    <h3 class="section-title">Jobs you may like</h3>
                    <div class="row">
                        <?php
                        // Fetch a few approved jobs so users can apply from dashboard
                        $jobsStmt = $conn->prepare("SELECT j.id, j.title, j.location, j.salary_range, j.job_type, c.name as company_name FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.status = 'approved' ORDER BY j.id DESC LIMIT 6");
                        $jobs = [];
                        if ($jobsStmt) {
                            $jobsStmt->execute();
                            $jobsRes = $jobsStmt->get_result();
                            if ($jobsRes) {
                                while ($r = $jobsRes->fetch_assoc()) $jobs[] = $r;
                            }
                        } else {
                            // fallback: raw query
                            $rawJobs = mysqli_query($conn, "SELECT j.id, j.title, j.location, j.salary_range, j.job_type, c.name as company_name FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.status = 'approved' ORDER BY j.id DESC LIMIT 6");
                            if ($rawJobs) while ($r = mysqli_fetch_assoc($rawJobs)) $jobs[] = $r;
                        }

                        if (count($jobs) === 0): ?>
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fas fa-briefcase"></i>
                                    <h4>No job suggestions right now</h4>
                                    <p class="text-muted">Check back later or browse all jobs.</p>
                                    <a href="/job-portal/public/jobs.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-search me-2"></i> Browse Jobs
                                    </a>
                                </div>
                            </div>
                        <?php else:
                            foreach ($jobs as $job): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title mb-2" style="color: var(--primary-dark);"><?= htmlspecialchars($job['title']) ?></h5>
                                            <div class="text-muted mb-2 small">
                                                <i class="fas fa-building me-1"></i> <?= htmlspecialchars($job['company_name']) ?>
                                            </div>
                                            <div class="mb-3 text-muted small">
                                                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['location']) ?>
                                            </div>
                                            <div class="mt-auto">
                                                <a href="/job-portal/public/job_view.php?id=<?= (int)$job['id'] ?>" class="btn view-details-btn w-100">
                                                    <i class="fas fa-eye me-2"></i> View Details & Apply
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>

                <!-- OVERVIEW STATS -->
                <div id="overview" class="mb-5">
                    <h3 class="section-title">Application Overview</h3>
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card total">
                                <div class="card-body">
                                    <i class="fas fa-paper-plane floating-element"></i>
                                    <h6>Total Applied</h6>
                                    <h2><?= $total ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card pending">
                                <div class="card-body">
                                    <i class="fas fa-clock floating-element"></i>
                                    <h6>Pending Review</h6>
                                    <h2><?= $pending ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card shortlisted">
                                <div class="card-body">
                                    <i class="fas fa-star floating-element"></i>
                                    <h6>Shortlisted</h6>
                                    <h2><?= $shortlisted ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stats-card rejected">
                                <div class="card-body">
                                    <i class="fas fa-times-circle floating-element"></i>
                                    <h6>Rejected Applications</h6>
                                    <h2><?= $rejected ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- APPLICATION LIST -->
                    <div class="col-lg-8 mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="section-title mb-0">Recent Applications</h3>
                            <span class="badge bg-primary"><?= $apps->num_rows ?> total</span>
                        </div>

                        <?php if ($apps && $apps->num_rows > 0): ?>
                            <?php while ($a = $apps->fetch_assoc()): ?>
                                <div class="card application-card <?= $a['status'] ?>">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h5 class="card-title mb-1" style="color: var(--primary-dark);"><?= htmlspecialchars($a['title']) ?></h5>
                                                <p class="card-text text-muted mb-1">
                                                    <i class="fas fa-building me-1"></i>
                                                    <?= htmlspecialchars($a['company_name']) ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Applied: <?= htmlspecialchars($a['applied_at']) ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <?php
                                                    $badgeClass = 'status-badge ';
                                                    if ($a['status'] === 'pending') $badgeClass .= 'badge-pending';
                                                    elseif ($a['status'] === 'shortlisted') $badgeClass .= 'badge-shortlisted';
                                                    elseif ($a['status'] === 'rejected') $badgeClass .= 'badge-rejected';
                                                    else $badgeClass .= 'bg-secondary text-white';
                                                ?>
                                                <span class="<?= $badgeClass ?>">
                                                    <i class="fas fa-<?= 
                                                        $a['status'] === 'pending' ? 'clock' : 
                                                        ($a['status'] === 'shortlisted' ? 'star' : 
                                                        ($a['status'] === 'rejected' ? 'times' : 'circle')) 
                                                    ?> me-1"></i>
                                                    <?= htmlspecialchars(ucfirst($a['status'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h4 class="text-muted">No Applications Yet</h4>
                                <p class="text-muted">You haven't applied to any jobs yet. Start your job search today!</p>
                                <a href="/job-portal/public/jobs.php/" class="btn btn-primary mt-3">
                                    <i class="fas fa-search me-2"></i>Browse Jobs
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- QUICK ACTIONS -->
                    <div class="col-lg-4 mb-5">
                        <h3 class="section-title">Quick Actions</h3>
                        
                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="quick-action-card">
                                    <i class="fas fa-user-edit"></i>
                                    <h5 style="color: var(--primary-dark);">Update Profile</h5>
                                    <p class="text-muted">Keep your profile information current</p>
                                    <a href="../profile/edit_profile.php" class="btn btn-primary w-100">
                                        <i class="fas fa-edit me-2"></i>Edit Profile
                                    </a>
                                </div>
                            </div>
                            
                            <div class="col-12 mb-4">
                                <div class="quick-action-card">
                                    <i class="fas fa-file-upload"></i>
                                    <h5 style="color: var(--primary-dark);">Upload CV</h5>
                                    <p class="text-muted">Upload your latest resume</p>
                                    <a href="../applications/upload_cv.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-upload me-2"></i>Upload CV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- QUICK STATS -->
                        <div class="mt-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title gradient-text fw-bold">Application Rate</h6>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?= $total > 0 ? ($shortlisted/$total)*100 : 0 ?>%"></div>
                                        <div class="progress-bar bg-warning" style="width: <?= $total > 0 ? ($pending/$total)*100 : 0 ?>%"></div>
                                        <div class="progress-bar bg-danger" style="width: <?= $total > 0 ? ($rejected/$total)*100 : 0 ?>%"></div>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <small class="text-success">
                                                <i class="fas fa-circle me-1"></i>
                                                Shortlisted: <?= $total > 0 ? round(($shortlisted/$total)*100, 1) : 0 ?>%
                                            </small>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-warning">
                                                <i class="fas fa-circle me-1"></i>
                                                Pending: <?= $total > 0 ? round(($pending/$total)*100, 1) : 0 ?>%
                                            </small>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-danger">
                                                <i class="fas fa-circle me-1"></i>
                                                Rejected: <?= $total > 0 ? round(($rejected/$total)*100, 1) : 0 ?>%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- footer (kept inside body so any scripts/output are in the right place) -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>