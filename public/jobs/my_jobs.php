<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get company ID
$stmt = $conn->prepare("SELECT id, name FROM companies WHERE user_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$companyResult = $stmt->get_result();

if ($companyResult->num_rows == 0) {
    header("Location: /job-portal/public/dashboard/company_dashboard.php");
    exit;
}

$company = $companyResult->fetch_assoc();
$company_id = $company['id'];
$company_name = $company['name'];

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$sql = "SELECT j.id, j.title, j.location, j.job_type, j.salary_range, j.status, j.created_at,
               COUNT(DISTINCT a.id) as application_count
        FROM jobs j
        LEFT JOIN applications a ON j.id = a.job_id
        WHERE j.company_id = ?";

$params = [$company_id];
$types = "i";

if (!empty($statusFilter)) {
    $sql .= " AND j.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " GROUP BY j.id";

// Sort options
if ($sortBy === 'oldest') {
    $sql .= " ORDER BY j.created_at ASC";
} elseif ($sortBy === 'applications') {
    $sql .= " ORDER BY application_count DESC";
} else { // newest (default)
    $sql .= " ORDER BY j.created_at DESC";
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$jobs = [];

while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

// Get statistics
$statsSql = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
    COUNT(*) as total
FROM jobs WHERE company_id = ?";

$statsStmt = $conn->prepare($statsSql);
$statsStmt->bind_param("i", $company_id);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Job Listings - <?= htmlspecialchars($company_name) ?></title>
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
            max-width: 1400px;
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
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(26, 83, 92, 0.05);
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
            border-radius: 20px 20px 0 0;
        }

        .filter-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .form-select, .form-control {
            border-color: rgba(26, 83, 92, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
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
            margin: 0 auto 1rem;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.total { background: var(--gradient); }
        .stat-icon.pending { background: var(--highlight-gradient); }
        .stat-icon.approved { background: var(--light-gradient); }
        .stat-icon.rejected { background: var(--accent-gradient); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Jobs Grid */
        .jobs-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .job-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-left: 6px solid var(--primary-dark);
            position: relative;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .job-card.pending { border-left-color: var(--highlight); }
        .job-card.approved { border-left-color: var(--primary-light); }
        .job-card.rejected { border-left-color: var(--accent); }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }

        .status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .badge-pending {
            background: rgba(255, 230, 109, 0.15);
            color: #d97706;
        }

        .badge-approved {
            background: rgba(78, 205, 196, 0.15);
            color: var(--primary-dark);
        }

        .badge-rejected {
            background: rgba(255, 107, 107, 0.15);
            color: #dc2626;
        }

        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
        }

        .meta-item i {
            color: var(--primary-light);
            width: 20px;
        }

        .job-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: var(--transition);
            border: none;
        }

        .btn-edit {
            background: var(--light-gradient);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(78, 205, 196, 0.3);
            color: white;
        }

        .btn-view {
            background: transparent;
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
        }

        .btn-view:hover {
            background: var(--light-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: rgba(255, 107, 107, 0.1);
            color: #dc2626;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        .btn-delete:hover {
            background: var(--accent-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        /* Empty State */
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

        .empty-text {
            color: var(--primary-dark);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .empty-subtext {
            color: #718096;
            margin-bottom: 2rem;
        }

        .btn-primary-lg {
            background: var(--gradient);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
        }

        .btn-primary-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 83, 92, 0.3);
            color: white;
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

            .page-title {
                font-size: 2rem;
            }

            .job-header {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .job-meta {
                grid-template-columns: 1fr;
            }

            .job-actions {
                flex-direction: column;
            }

            .btn-sm {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="page-container">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-briefcase me-2"></i>My Job Listings
                    </h1>
                    <p class="page-subtitle">
                        Manage all job postings for <?= htmlspecialchars($company_name) ?>
                    </p>
                </div>
                <a href="add_job.php" class="btn btn-primary-lg">
                    <i class="fas fa-plus me-2"></i>Post New Job
                </a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Jobs</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>

            <div class="stat-card approved">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $stats['approved'] ?></div>
                <div class="stat-label">Approved</div>
            </div>

            <div class="stat-card rejected">
                <div class="stat-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?= $stats['rejected'] ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section fade-in">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="statusFilter" class="filter-label">
                        <i class="fas fa-filter me-2"></i>Filter by Status
                    </label>
                    <select class="form-select" id="statusFilter" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="sortBy" class="filter-label">
                        <i class="fas fa-sort me-2"></i>Sort by
                    </label>
                    <select class="form-select" id="sortBy" name="sort">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="applications" <?= $sortBy === 'applications' ? 'selected' : '' ?>>Most Applications</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary-lg">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <?php if (!empty($statusFilter) || $sortBy !== 'newest'): ?>
                        <a href="my_jobs.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-redo me-2"></i>Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Jobs List -->
        <?php if (count($jobs) > 0): ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $index => $job): ?>
                    <div class="job-card <?= $job['status'] ?> fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                        <div class="job-header">
                            <h3 class="job-title"><?= htmlspecialchars($job['title']) ?></h3>
                            <span class="status-badge badge-<?= $job['status'] ?>">
                                <?= ucfirst($job['status']) ?>
                            </span>
                        </div>

                        <div class="job-meta">
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($job['location']) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?= htmlspecialchars($job['job_type']) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span><?= htmlspecialchars($job['salary_range']) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-users"></i>
                                <span><?= $job['application_count'] ?> Application<?= $job['application_count'] != 1 ? 's' : '' ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?= date('M j, Y', strtotime($job['created_at'])) ?></span>
                            </div>
                        </div>

                        <div class="job-actions">
                            <a href="edit_job.php?id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-edit">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                            <a href="../job_view.php?id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-view" target="_blank">
                                <i class="fas fa-eye me-2"></i>View
                            </a>
                            <a href="delete_job.php?id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this job?');">
                                <i class="fas fa-trash me-2"></i>Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state fade-in">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <p class="empty-text">No Jobs Found</p>
                <p class="empty-subtext">
                    <?php if (!empty($statusFilter)): ?>
                        No jobs with status "<?= ucfirst($statusFilter) ?>". Try changing your filters.
                    <?php else: ?>
                        You haven't posted any jobs yet. Start by creating your first job listing!
                    <?php endif; ?>
                </p>
                <a href="add_job.php" class="btn btn-primary-lg">
                    <i class="fas fa-plus me-2"></i>Post Your First Job
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add fade-in animations on page load
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
