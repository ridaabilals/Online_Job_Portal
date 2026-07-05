<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

// ✅ Allow ONLY admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

// ✅ Fetch all jobs
$sql = "
SELECT jobs.*, companies.name AS company_name
FROM jobs
LEFT JOIN companies ON jobs.company_id = companies.id
ORDER BY jobs.id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

// Get job statistics
$totalJobs = mysqli_num_rows($result);
$pendingJobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'"))['count'];
$approvedJobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM jobs WHERE status = 'approved'"))['count'];
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<style>
:root {
    --primary-dark: #1A535C;
    --primary-light: #4ECDC4;
    --background: #F7FFF7;
    --accent: #FF6B6B;
    --highlight: #FFE66D;
    --company-gradient: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
    --accent-gradient: linear-gradient(135deg, var(--accent) 0%, #FF8E8E 100%);
    --highlight-gradient: linear-gradient(135deg, var(--highlight) 0%, #FFEE9D 100%);
    --primary-light-gradient: linear-gradient(135deg, var(--primary-light) 0%, #6EF5ED 100%);
}

.admin-jobs-page {
    background: var(--background);
    min-height: 100vh;
    padding: 30px 0;
}

.page-header-admin {
    background: var(--company-gradient);
    color: white;
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
    position: relative;
    overflow: hidden;
}

.page-header-admin::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.stats-card-admin {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.stats-card-admin::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-light);
}

.stats-card-admin:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stats-card-admin.total { 
    background: var(--company-gradient); 
}
.stats-card-admin.pending { 
    background: var(--highlight-gradient);
    color: #333;
}
.stats-card-admin.approved { 
    background: var(--primary-light-gradient); 
}

.jobs-table-container {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    border-top: 4px solid var(--primary-light);
}

.table-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.jobs-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.jobs-table thead th {
    background: var(--company-gradient);
    color: white;
    font-weight: 600;
    padding: 1rem 1.5rem;
    border: none;
    position: relative;
}

.jobs-table thead th:first-child {
    border-top-left-radius: 15px;
}

.jobs-table thead th:last-child {
    border-top-right-radius: 15px;
}

.jobs-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e9ecef;
}

.jobs-table tbody tr:hover {
    background: rgba(78, 205, 196, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.jobs-table tbody td {
    padding: 1.25rem 1.5rem;
    vertical-align: middle;
    border: none;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.badge-pending {
    background: var(--highlight-gradient);
    color: #333;
}

.badge-approved {
    background: var(--primary-light-gradient);
    color: white;
}

.admin-btn {
    border-radius: 25px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.25rem;
}

.admin-btn-success {
    background: var(--primary-light-gradient);
    color: white;
}

.admin-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
    color: white;
}

.admin-btn-danger {
    background: var(--accent-gradient);
    color: white;
}

.admin-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
    color: white;
}

.flash-message {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.flash-success {
    border-left: 4px solid var(--primary-light);
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
    color: var(--primary-dark);
}

.flash-danger {
    border-left: 4px solid var(--accent);
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #dc2626;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.job-title {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.25rem;
}

.job-company {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.job-meta {
    display: flex;
    align-items: center;
    color: #666;
    font-size: 0.85rem;
    margin-top: 0.25rem;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--primary-dark);
}

.features-box {
    background: var(--background);
    border-radius: 15px;
    padding: 2rem;
    margin-top: 2rem;
    border: 2px dashed var(--highlight);
    text-align: center;
}

.features-box h4 {
    color: var(--primary-dark);
    margin-bottom: 1rem;
}

.features-box p {
    color: #666;
    margin-bottom: 1.5rem;
}

.job-type-badge {
    background: #e9ecef;
    color: #495057;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .admin-jobs-page {
        padding: 15px 0;
    }
    
    .page-header-admin {
        padding: 1.5rem;
    }
    
    .jobs-table-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .admin-btn {
        width: 100%;
        justify-content: center;
    }
    
    .jobs-table {
        min-width: 800px;
    }
}
</style>

<div class="admin-jobs-page">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header-admin">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">💼 Job Management</h1>
                    <p class="lead mb-0">Manage all job postings, approve pending jobs, and maintain job listings.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                        <small class="fw-bold" style="color: var(--primary-dark) !important;"><?= date('l, F j, Y') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['approved'])): ?>
            <div class="alert flash-message flash-success">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Success!</h5>
                        Job has been approved successfully.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert flash-message flash-danger">
                <div class="d-flex align-items-center">
                    <i class="fas fa-trash-alt me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Deleted</h5>
                        Job has been deleted successfully.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card-admin total">
                    <div class="card-body">
                        <i class="fas fa-briefcase mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Total Jobs</h6>
                        <h3><?= $totalJobs ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-admin pending">
                    <div class="card-body">
                        <i class="fas fa-clock mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Pending Approval</h6>
                        <h3><?= $pendingJobs ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-admin approved">
                    <div class="card-body">
                        <i class="fas fa-check-circle mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Approved Jobs</h6>
                        <h3><?= $approvedJobs ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="jobs-table-container">
            <div class="table-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0" style="color: var(--primary-dark);">All Job Listings</h3>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge fs-6" style="background: var(--primary-dark);"><?= $totalJobs ?> jobs</span>
                    </div>
                </div>
            </div>

            <?php if ($totalJobs > 0): ?>
                <div class="table-responsive">
                    <table class="jobs-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Job Details</th>
                                <th style="width: 120px;">Type</th>
                                <th style="width: 140px;">Status</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($result, 0); // Reset result pointer
                            while ($row = mysqli_fetch_assoc($result)): 
                            ?>
                                <tr>
                                    <td>
                                        <strong>#<?= $row['id'] ?></strong>
                                    </td>
                                    <td>
                                        <div class="job-title"><?= htmlspecialchars($row['title']) ?></div>
                                        <div class="job-company">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($row['company_name'] ?? 'N/A') ?>
                                        </div>
                                        <?php if (!empty($row['location'])): ?>
                                            <div class="job-meta">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($row['location']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="job-type-badge">
                                            <?= htmlspecialchars($row['job_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($row['status'] ?? '') === 'pending'): ?>
                                            <span class="status-badge badge-pending">
                                                <i class="fas fa-clock"></i>
                                                Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge badge-approved">
                                                <i class="fas fa-check"></i>
                                                Approved
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                <!-- ✅ APPROVE -->
                                                <form method="POST" action="approve_job.php" class="mb-0">
                                                    <input type="hidden" name="job_id" value="<?= $row['id'] ?>">
                                                    <button class="btn admin-btn admin-btn-success">
                                                        <i class="fas fa-check"></i>
                                                        Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- ✅ DELETE -->
                                            <form method="POST" action="delete_job.php" class="mb-0"
                                                  onsubmit="return confirm('Are you sure you want to delete this job? This action cannot be undone.');">
                                                <input type="hidden" name="job_id" value="<?= $row['id'] ?>">
                                                <button class="btn admin-btn admin-btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Jobs Found</h3>
                    <p class="text-muted">There are no job listings in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>

        

    </div>
</div>

<script>
// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    const statsCards = document.querySelectorAll('.stats-card-admin');
    const tableRows = document.querySelectorAll('.jobs-table tbody tr');
    
    // Add subtle animation on page load for stats cards
    statsCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Add animation for table rows
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.5s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, (index + statsCards.length) * 50);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>