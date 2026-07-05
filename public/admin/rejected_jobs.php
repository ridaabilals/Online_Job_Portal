<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

// Fetch rejected jobs (attempt primary query, fall back to a simpler one if it fails)
$sql = "SELECT j.id, j.title, j.location, j.job_type, j.salary_range, j.created_at, c.name AS company_name FROM jobs j LEFT JOIN companies c ON j.company_id = c.id WHERE j.status = 'rejected' ORDER BY j.id DESC";
$res = $conn->query($sql);

// If the query failed and produced no MySQL error message (or failed due to a missing column),
// try a simpler fallback (omit created_at) so the page can still show rejected jobs.
$used_fallback = false;
if ($res === false) {
    $err = mysqli_error($conn);
    $errno = mysqli_errno($conn);

    // Try fallback query without created_at
    $fallback_sql = "SELECT j.id, j.title, j.location, j.job_type, j.salary_range, c.name AS company_name FROM jobs j LEFT JOIN companies c ON j.company_id = c.id WHERE j.status = 'rejected' ORDER BY j.id DESC";
    $res = $conn->query($fallback_sql);
    if ($res !== false) {
        $used_fallback = true;
    } else {
        // keep original error for debug display below; don't overwrite
        // store the original error details for diagnostic output
        $query_error = [
            'errno' => $errno,
            'error' => $err,
            'sql' => $sql
        ];
    }
}

// Get statistics
$rejectedCount = $res ? $res->num_rows : 0;
$totalJobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM jobs"))['count'];
$pendingJobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'"))['count'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>

.rejected-jobs-page {
    background: var(--background);
    min-height: 100vh;
    padding: 30px 0;
}

.page-header-admin {
    background: var(--accent-gradient);
    color: white;
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(255, 107, 107, 0.3);
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

.stats-card-admin.rejected { 
    background: var(--accent-gradient); 
}
.stats-card-admin.total { 
    background: var(--company-gradient); 
}
.stats-card-admin.pending { 
    background: var(--highlight-gradient);
    color: #333;
}

.jobs-container {
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

.job-card {
    border: none;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    background: white;
    border-left: 4px solid var(--accent);
    position: relative;
    overflow: hidden;
}

.job-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--accent-gradient);
}

.job-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.job-title {
    font-weight: 700;
    color: var(--primary-dark);
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
    flex: 1;
}

.company-name {
    color: #666;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.company-name i {
    margin-right: 0.5rem;
    color: var(--primary-dark);
}

.job-meta {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.job-meta i {
    margin-right: 0.75rem;
    width: 16px;
    color: var(--primary-dark);
}

.job-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.admin-btn {
    border-radius: 25px;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    justify-content: center;
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
    border-left: 4px solid var(--primary-light);
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
    color: var(--primary-dark);
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--primary-light);
}

.job-date {
    color: #999;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.job-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.job-detail-item {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 10px;
    border-left: 3px solid var(--primary-dark);
}

.job-detail-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.job-detail-value {
    font-weight: 600;
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

.rejection-reason {
    background: #fef2f2;
    padding: 1rem;
    border-radius: 10px;
    margin-top: 1rem;
    border-left: 3px solid var(--accent);
}

.rejection-reason-label {
    font-weight: 600;
    color: var(--accent);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rejection-reason-text {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .rejected-jobs-page {
        padding: 15px 0;
    }
    
    .page-header-admin {
        padding: 1.5rem;
    }
    
    .jobs-container {
        padding: 1rem;
    }
    
    .job-actions {
        flex-direction: column;
    }
    
    .job-details-grid {
        grid-template-columns: 1fr;
    }
    
    .job-header {
        flex-direction: column;
    }
    
    .rejected-badge {
        margin-top: 0.5rem;
        align-self: flex-start;
    }
}
</style>

<div class="rejected-jobs-page">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header-admin">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">🚫 Rejected Jobs</h1>
                    <p class="lead mb-0">Review and manage jobs that have been rejected. You can approve or permanently delete them.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                        <small class="fw-bold" style="color: var(--accent) !important;"><?= date('l, F j, Y') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert flash-message">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Notification</h5>
                        <?= htmlspecialchars($_SESSION['flash']) ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card-admin rejected">
                    <div class="card-body">
                        <i class="fas fa-ban mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Rejected Jobs</h6>
                        <h3><?= $rejectedCount ?></h3>
                    </div>
                </div>
            </div>
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
                        <h6>Pending Jobs</h6>
                        <h3><?= $pendingJobs ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Container -->
        <div class="jobs-container">
            <div class="table-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0" style="color: var(--primary-dark);">Rejected Job Listings</h3>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge fs-6" style="background: var(--accent);"><?= $rejectedCount ?> rejected jobs</span>
                    </div>
                </div>
            </div>

            <?php if (!$res): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load rejected jobs.
                            <?php if (!empty($query_error)): ?>
                                <div class="mt-2 small text-danger">SQL Error (<?= (int)$query_error['errno'] ?>): <?= htmlspecialchars($query_error['error']) ?></div>
                                <div class="mt-1 small text-muted">Query: <code><?= htmlspecialchars($query_error['sql']) ?></code></div>
                            <?php else: ?>
                                <?php if (!empty(mysqli_error($conn))): ?>
                                    <div class="mt-2 small text-danger"><?= htmlspecialchars(mysqli_error($conn)) ?></div>
                                <?php else: ?>
                                    <div class="mt-2 small text-muted">No further details available.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                </div>
            <?php elseif ($res->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color: var(--primary-light);"></i>
                    <h3>No Rejected Jobs</h3>
                    <p class="text-muted">Great! There are no rejected jobs in the system.</p>
                    <a href="/job-portal/public/admin/manage_jobs.php" class="btn admin-btn-success admin-btn mt-3" style="width: auto;">
                        <i class="fas fa-arrow-left me-2"></i>Back to All Jobs
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php while ($p = $res->fetch_assoc()): ?>
                        <div class="col-12 mb-4">
                            <div class="job-card">
                                <div class="job-header">
                                    <div class="flex-grow-1">
                                        <h5 class="job-title"><?= htmlspecialchars($p['title']) ?></h5>
                                        <div class="company-name">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($p['company_name'] ?? 'Unknown Company') ?>
                                        </div>
                                    </div>
                                    <span class="rejected-badge">
                                        <i class="fas fa-times-circle"></i>
                                        Rejected
                                    </span>
                                </div>


                                <div class="job-details-grid">
                                    <?php if (!empty($p['location'])): ?>
                                        <div class="job-detail-item">
                                            <div class="job-detail-label">Location</div>
                                            <div class="job-detail-value">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($p['location']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($p['job_type'])): ?>
                                        <div class="job-detail-item">
                                            <div class="job-detail-label">Job Type</div>
                                            <div class="job-detail-value">
                                                <i class="fas fa-briefcase me-1"></i>
                                                <?= htmlspecialchars($p['job_type']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($p['salary_range'])): ?>
                                        <div class="job-detail-item">
                                            <div class="job-detail-label">Salary Range</div>
                                            <div class="job-detail-value">
                                                <i class="fas fa-money-bill-wave me-1"></i>
                                                <?= htmlspecialchars($p['salary_range']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($p['created_at'])): ?>
                                        <div class="job-detail-item">
                                            <div class="job-detail-label">Created</div>
                                            <div class="job-detail-value">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('M j, Y', strtotime($p['created_at'])) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="job-actions">
                                    <!-- Approve (will mark as approved) -->
                                    <form method="POST" action="/job-portal/public/admin/approve_job_action.php" class="mb-0 flex-grow-1">
                                        <input type="hidden" name="job_id" value="<?= (int)$p['id'] ?>">
                                        <button class="btn admin-btn admin-btn-success">
                                            <i class="fas fa-check"></i>
                                            Approve Job
                                        </button>
                                    </form>

                                    <!-- Delete permanently -->
                                    <form method="POST" action="/job-portal/public/admin/delete_job.php" class="mb-0 flex-grow-1"
                                          onsubmit="return confirm('Are you sure you want to permanently delete this job? This action cannot be undone.');">
                                        <input type="hidden" name="job_id" value="<?= (int)$p['id'] ?>">
                                        <button class="btn admin-btn admin-btn-danger">
                                            <i class="fas fa-trash"></i>
                                            Delete Permanently
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Features Box -->
        <div class="features-box">
            <h4><i class="fas fa-archive me-2"></i>Rejection Management</h4>
            <p class="text-muted">Use bulk actions and advanced tools to efficiently manage rejected job listings.</p>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn admin-btn" style="background: var(--company-gradient); color: white;">
                    <i class="fas fa-download me-2"></i>Export Rejected
                </button>
                <button class="btn admin-btn" style="background: var(--highlight-gradient); color: #333;">
                    <i class="fas fa-trash-restore me-2"></i>Bulk Restore
                </button>
            </div>
        </div>

    </div>
</div>

<script>
// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    const statsCards = document.querySelectorAll('.stats-card-admin');
    const jobCards = document.querySelectorAll('.job-card');
    
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
    
    // Add animation for job cards
    jobCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateX(0)';
        }, (index + statsCards.length) * 50);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>