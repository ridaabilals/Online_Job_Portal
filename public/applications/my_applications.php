<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /job-portal/public/login.php");
    exit;
}

// Basic context information
$user_id = $_SESSION['user_id'];
$isCompany = isset($_SESSION['is_company']) && $_SESSION['is_company'] == 1;

// If company: find the real company id for this logged-in company user
if ($isCompany) {
    $stmt = $conn->prepare("SELECT id, name FROM companies WHERE user_id = ? LIMIT 1");
    if ($stmt === false) {
        // Prepare failed — show a helpful error instead of crashing on bind_param()
        $err = mysqli_error($conn);
        // Prefer a graceful redirect so admins / users don't see raw SQL errors.
        $_SESSION['flash'] = 'Database error (companies lookup): ' . $err;
        header('Location: /job-portal/public/dashboard/company_dashboard.php');
        exit;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $companyRes = $stmt->get_result();

    if ($companyRes->num_rows === 0) {
        // No company profile yet — redirect back to company page where profile is auto-created
        header('Location: /job-portal/public/dashboard/company_dashboard.php');
        exit;
    }

    $company = $companyRes->fetch_assoc();
    $company_id = (int)$company['id'];
    $company_name = $company['name'];

    // Get application statistics for the company
    $totalApps = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.company_id = $company_id"
    ))['count'];
    $shortlistedApps = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.company_id = $company_id AND a.status IN ('shortlisted', 'shortlist')"
    ))['count'];
    $pendingApps = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.company_id = $company_id AND a.status NOT IN ('shortlisted', 'shortlist', 'rejected', 'reject')"
    ))['count'];

    // Prepared query to fetch applications for jobs that belong to this company
    $sql = "SELECT applications.*, users.username, users.email, jobs.title, jobs.id as job_id
            FROM applications
            JOIN users ON applications.user_id = users.id
            JOIN jobs ON applications.job_id = jobs.id
            WHERE jobs.company_id = ?
            ORDER BY applications.id DESC";
} else {
    // Non-company user: fetch their personal application summary and list
    $company_id = null;
    $company_name = null;

    // Stats for the applicant (current user)
    $u = (int)$user_id;
    $totalApps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM applications WHERE user_id = $u"))['count'];
    $shortlistedApps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM applications WHERE user_id = $u AND status IN ('shortlisted', 'shortlist')"))['count'];
    $pendingApps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM applications WHERE user_id = $u AND status NOT IN ('shortlisted', 'shortlist', 'rejected', 'reject')"))['count'];

    $sql = "SELECT applications.*, jobs.title, jobs.id as job_id, c.name as company_name
            FROM applications
            JOIN jobs ON applications.job_id = jobs.id
            LEFT JOIN companies c ON jobs.company_id = c.id
            WHERE applications.user_id = ?
            ORDER BY applications.id DESC";
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Failed to prepare applications query — try a safe raw/fallback query
    $queryError = mysqli_error($conn);
    $queryErrNo = mysqli_errno($conn);

    // If counts indicate there ARE rows, attempt a raw query fallback so users still see rows
    if (!empty($totalApps) && (int)$totalApps > 0) {
        if ($isCompany) {
            $raw = "SELECT applications.*, users.username, users.email, jobs.title, jobs.id as job_id
                    FROM applications
                    JOIN users ON applications.user_id = users.id
                    JOIN jobs ON applications.job_id = jobs.id
                    WHERE jobs.company_id = $company_id
                    ORDER BY applications.id DESC";
        } else {
            $raw = "SELECT applications.*, jobs.title, jobs.id as job_id, c.name as company_name
                    FROM applications
                    JOIN jobs ON applications.job_id = jobs.id
                    LEFT JOIN companies c ON jobs.company_id = c.id
                    WHERE applications.user_id = $user_id
                    ORDER BY applications.id DESC";
        }

        $fallbackRes = mysqli_query($conn, $raw);
        if ($fallbackRes && mysqli_num_rows($fallbackRes) > 0) {
            $result = $fallbackRes;
            // Helpful note that fallback succeeded.
            $_SESSION['flash'] = 'Note: used fallback query as prepared() failed for applications query.';
        } else {
            // Fallback didn't return rows — surface richer diagnostics for company users
            $fallbackErr = mysqli_error($conn);
            $fallbackErrNo = mysqli_errno($conn);
            if ($isCompany) {
                $_SESSION['flash'] = "Database error (applications query) — prepare_errno={$queryErrNo}, prepare_error='{$queryError}' — fallback_errno={$fallbackErrNo}, fallback_error='{$fallbackErr}'";
            } else {
                $_SESSION['flash'] = 'Database error (applications query): unable to fetch application rows.';
            }
            $result = false;
        }
    } else {
        // Nothing to show — surface the prepare error (if any) but don't alarm the user unnecessarily
        if ($isCompany) {
            $_SESSION['flash'] = "Database error (applications query) — errno={$queryErrNo}, err='{$queryError}'";
        } else {
            $_SESSION['flash'] = 'Database error (applications query): unexpected error.';
        }
        $result = false;
    }
} else {
    // Pick the correct param for the prepared statement depending on the view
    $bindValue = $isCompany ? $company_id : $user_id;
    $stmt->bind_param("i", $bindValue);
    $ok = $stmt->execute();
    $result = $stmt->get_result();

    // If counts show applications exist but the prepared query returned nothing,
    // fall back to a safe inline query (helps in environments without mysqlnd
    // or strange prepared/binding issues). This keeps the page working.
    if ($totalApps > 0 && (!$result || $result->num_rows === 0)) {
        if ($isCompany) {
            $rawSql = "SELECT applications.*, users.username, users.email, jobs.title, jobs.id as job_id
                       FROM applications
                       JOIN users ON applications.user_id = users.id
                       JOIN jobs ON applications.job_id = jobs.id
                       WHERE jobs.company_id = $company_id
                       ORDER BY applications.id DESC";
        } else {
            $rawSql = "SELECT applications.*, jobs.title, jobs.id as job_id, c.name as company_name
                       FROM applications
                       JOIN jobs ON applications.job_id = jobs.id
                       LEFT JOIN companies c ON jobs.company_id = c.id
                       WHERE applications.user_id = $user_id
                       ORDER BY applications.id DESC";
        }

        $fallback = mysqli_query($conn, $rawSql);
        if ($fallback && mysqli_num_rows($fallback) > 0) {
            $result = $fallback;
            $_SESSION['flash'] = 'Notice: used fallback query to load applications.';
        } else {
            $_SESSION['flash'] = 'Unable to load applications via prepared query or fallback. Check DB.';
        }
    }
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<style>


.applications-page {
    background: var(--background);
    min-height: 100vh;
    padding: 30px 0;
}

.page-header-company {
    background: var(--company-gradient);
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
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.stats-card-company {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    color: white;
    text-align: center;
}

.stats-card-company:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stats-card-company.total { 
    background: var(--company-gradient);
}
.stats-card-company.shortlisted { 
    background: linear-gradient(135deg, var(--primary-light) 0%, #6EF5ED 100%); 
}
.stats-card-company.pending { 
    background: var(--highlight-gradient);
    color: #333;
}

.applications-container {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.table-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.application-card {
    border: none;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    background: white;
    border-left: 4px solid var(--primary-dark);
    position: relative;
    overflow: hidden;
}

.application-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--company-gradient);
}

.application-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.applicant-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--company-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    margin-right: 1.5rem;
}

.applicant-info {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.applicant-details h5 {
    color: var(--primary-dark);
    margin-bottom: 0.25rem;
    font-weight: 700;
}

.applicant-details p {
    color: #666;
    margin: 0;
    font-size: 0.9rem;
}

.job-details {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 3px solid var(--primary-dark);
}

.job-title {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.application-date {
    color: #666;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cover-letter {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-left: 3px solid var(--primary-light);
}

.cover-letter-label {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cover-letter-text {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    max-height: 80px;
    overflow: hidden;
    position: relative;
}

.cover-letter-text.expanded {
    max-height: none;
}

.read-more-btn {
    background: none;
    border: none;
    color: var(--primary-dark);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
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
    background: #e5e7eb;
    color: #374151;
}

.badge-shortlisted {
    background: linear-gradient(135deg, var(--primary-light) 0%, #6EF5ED 100%);
    color: white;
}

.badge-rejected {
    background: var(--accent-gradient);
    color: white;
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.company-btn {
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

.company-btn-primary {
    background: var(--company-gradient);
    color: white;
}

.company-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(26, 83, 92, 0.4);
    color: white;
}

.company-btn-accent {
    background: var(--accent-gradient);
    color: white;
}

.company-btn-accent:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
    color: white;
}

.company-btn-highlight {
    background: var(--highlight-gradient);
    color: #333;
}

.company-btn-highlight:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 230, 109, 0.4);
    color: #333;
}

.company-btn-disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
}

.resume-link {
    background: var(--company-gradient);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.resume-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(26, 83, 92, 0.4);
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
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: var(--primary-dark);
}

.flash-danger {
    border-left: 4px solid var(--accent);
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #dc2626;
}

.flash-warning {
    border-left: 4px solid var(--highlight);
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #92400e;
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

@media (max-width: 768px) {
    .applications-page {
        padding: 15px 0;
    }
    
    .page-header-company {
        padding: 1.5rem;
    }
    
    .applications-container {
        padding: 1rem;
    }
    
    .applicant-info {
        flex-direction: column;
        text-align: center;
    }
    
    .applicant-avatar {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="applications-page">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header-company">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">📥 Job Applications</h1>
                    <?php if ($isCompany): ?>
                        <p class="lead mb-0">Manage applications for your job postings and find the perfect candidates.</p>
                    <?php else: ?>
                        <p class="lead mb-0">Track and manage applications you've submitted.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                        <?php if ($isCompany): ?>
                            <small class="text-primary fw-bold" style="color: var(--primary-dark) !important;"><?= htmlspecialchars($company_name) ?></small>
                        <?php else: ?>
                            <?php
                            // fetch user display name for personal view
                            $uStmt = $conn->prepare('SELECT username, full_name FROM users WHERE id = ? LIMIT 1');
                            $displayName = 'My Applications';
                            if ($uStmt) {
                                $uStmt->bind_param('i', $user_id);
                                $uStmt->execute();
                                $uRow = $uStmt->get_result()->fetch_assoc();
                                if ($uRow) $displayName = !empty($uRow['full_name']) ? $uRow['full_name'] : $uRow['username'];
                            }
                            ?>
                            <small class="text-primary fw-bold" style="color: var(--primary-dark) !important;"><?= htmlspecialchars($displayName) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert flash-message <?php echo strpos($_SESSION['flash'],'Unable')!==false ? 'flash-danger' : 'flash-success'; ?>">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Notice</h5>
                        <?= htmlspecialchars($_SESSION['flash']) ?>
                        <?php if ($isCompany && stripos($_SESSION['flash'], 'Database error (applications query)') !== false):
                            // Show more verbose debug output for company accounts only (reveal whitespace & non-printables)
                            $raw = $_SESSION['flash'];
                            $repl = str_replace(["\r","\n","\t"], ['\\r','\\n','\\t'], $raw);
                        ?>
                        <pre style="margin-top:.5rem;background:#f8f9fa;padding:.75rem;border-radius:8px;color:#333;white-space:pre-wrap;">DIAG: <?= htmlspecialchars($repl) ?></pre>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
        <?php if (isset($_GET['shortlisted'])): ?>
            <div class="alert flash-message flash-success">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Success!</h5>
                        Application shortlisted successfully.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
            <div class="alert flash-message flash-danger">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Access Denied</h5>
                        You are not authorized to manage this application.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['rejected'])): ?>
            <div class="alert flash-message flash-warning">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Application Updated</h5>
                        Application marked as rejected.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['hired'])): ?>
            <div class="alert flash-message flash-success">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Success!</h5>
                        Applicant has been hired successfully.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card-company total">
                    <div class="card-body">
                        <i class="fas fa-file-alt mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Total Applications</h6>
                        <h3><?= $totalApps ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-company shortlisted">
                    <div class="card-body">
                        <i class="fas fa-star mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Shortlisted</h6>
                        <h3><?= $shortlistedApps ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-company pending">
                    <div class="card-body">
                        <i class="fas fa-clock mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Pending Review</h6>
                        <h3><?= $pendingApps ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Container -->
        <div class="applications-container">
            <div class="table-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0">All Applications</h3>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge fs-6" style="background: var(--primary-dark);"><?= $totalApps ?> applications</span>
                    </div>
                </div>
            </div>

            <?php
            // Diagnostics are noisy for users — log to server instead for developers.
            if ($isCompany && isset($totalApps) && (int)$totalApps > 0) {
                $debug_present = ($result !== false && $result !== null) ? 'yes' : 'no';
                $debug_numrows = (is_object($result) && isset($result->num_rows)) ? $result->num_rows : 'n/a';
                error_log(sprintf(
                    "applications_debug: isCompany=%s, company_id=%s, totalApps=%d, result_present=%s, result_num_rows=%s",
                    $isCompany ? 'yes' : 'no',
                    $company_id ?? 'null',
                    (int)$totalApps,
                    $debug_present,
                    $debug_numrows
                ));
            }
            ?>

            <?php if ($result && $result->num_rows > 0): ?>
                <div class="row">
                    <?php while($app = $result->fetch_assoc()): 
                        // For company view we show applicant name/initial. For user view, use job title initial.
                        if ($isCompany) {
                            $initial = strtoupper(substr($app['username'] ?? '', 0, 1));
                        } else {
                            $initial = strtoupper(substr($app['title'] ?? '', 0, 1));
                        }
                        $status = $app['status'];
                        $isShortlisted = in_array($status, ['shortlisted','shortlist']);
                        $isRejected = in_array($status, ['rejected','reject']);
                    ?>
                        <div class="col-12 mb-4">
                            <div class="application-card">
                                <!-- Applicant Info / Job Info depending on view -->
                                <?php if ($isCompany): ?>
                                    <div class="applicant-info">
                                        <div class="applicant-avatar">
                                            <?= $initial ?>
                                        </div>
                                        <div class="applicant-details">
                                            <h5><?= htmlspecialchars($app['username']) ?></h5>
                                            <p>
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($app['email']) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="applicant-info">
                                        <div style="width:60px;height:60px;border-radius:12px;background:var(--company-gradient);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;margin-right:1.5rem;">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <div class="applicant-details">
                                            <h5><?= htmlspecialchars($app['title']) ?></h5>
                                            <p>
                                                <i class="fas fa-building me-1"></i>
                                                <?= htmlspecialchars($app['company_name'] ?? '—') ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Job Details -->
                                <div class="job-details">
                                    <div class="job-title">
                                        <i class="fas fa-briefcase"></i>
                                        <?= htmlspecialchars($app['title']) ?>
                                    </div>
                                    <div class="application-date">
                                        <i class="fas fa-calendar"></i>
                                        Applied: Recently
                                    </div>
                                </div>

                                <!-- Resume -->
                                <div class="mb-3">
                                    <?php if (!empty($app['resume_path'])): ?>
                                        <?php
                                            // Detect actual resume directory by checking which path exists on disk
                                            $resumeFilename = basename($app['resume_path']); // Extract filename only
                                            $publicDir = realpath(__DIR__ . '/../');
                                            
                                            $candidates = [
                                                $publicDir . '/uploads/resumes/' . $resumeFilename,
                                                $publicDir . '/assets/uploads/resumes/' . $resumeFilename,
                                                $publicDir . '/uploads/' . $resumeFilename,
                                                $publicDir . '/assets/uploads/' . $resumeFilename,
                                            ];
                                            
                                            $resumeUrl = null;
                                            $webPaths = [
                                                '/job-portal/public/uploads/resumes/' . htmlspecialchars($resumeFilename),
                                                '/job-portal/public/assets/uploads/resumes/' . htmlspecialchars($resumeFilename),
                                                '/job-portal/public/uploads/' . htmlspecialchars($resumeFilename),
                                                '/job-portal/public/assets/uploads/' . htmlspecialchars($resumeFilename),
                                            ];
                                            
                                            // Find first matching file on disk
                                            foreach ($candidates as $idx => $fsPath) {
                                                if (file_exists($fsPath)) {
                                                    $resumeUrl = $webPaths[$idx];
                                                    break;
                                                }
                                            }
                                            
                                            // Fallback: try the stored path as-is (in case it's a full path)
                                            if (!$resumeUrl && !empty($app['resume_path'])) {
                                                $resumeUrl = '/job-portal/public/uploads/resumes/' . htmlspecialchars($resumeFilename);
                                            }
                                        ?>
                                        <?php if ($resumeUrl): ?>
                                            <a href="<?= $resumeUrl ?>" 
                                               target="_blank" 
                                               class="resume-link"
                                               title="Download: <?= htmlspecialchars($resumeFilename) ?>">
                                                <i class="fas fa-download"></i>
                                                View Resume
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="fas fa-file me-1"></i>
                                                Resume file not found (<?= htmlspecialchars($resumeFilename) ?>)
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-file me-1"></i>
                                            No resume attached
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Cover Letter -->
                                <?php if (!empty($app['cover_letter'])): ?>
                                    <div class="cover-letter">
                                        <div class="cover-letter-label">
                                            <i class="fas fa-envelope-open-text"></i>
                                            Cover Letter
                                        </div>
                                        <div class="cover-letter-text" id="cover-<?= $app['id'] ?>">
                                            <?= nl2br(htmlspecialchars($app['cover_letter'])) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Status and Actions -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($isShortlisted): ?>
                                            <span class="status-badge badge-shortlisted">
                                                <i class="fas fa-star"></i>
                                                Shortlisted
                                            </span>
                                        <?php elseif ($isRejected): ?>
                                            <span class="status-badge badge-rejected">
                                                <i class="fas fa-times"></i>
                                                Rejected
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge badge-pending">
                                                <i class="fas fa-clock"></i>
                                                <?= htmlspecialchars(ucfirst($status)) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="action-buttons">
                                        <?php if ($isShortlisted): ?>
                                            <button class="btn company-btn company-btn-disabled" disabled>
                                                <i class="fas fa-check"></i>
                                                Already Shortlisted
                                            </button>
                                        <?php elseif ($isRejected): ?>
                                            <button class="btn company-btn company-btn-disabled" disabled>
                                                <i class="fas fa-times"></i>
                                                Already Rejected
                                            </button>
                                        <?php else: ?>
                                            <?php if ($isCompany): ?>
                                                <!-- Shortlist Form -->
                                                <form method="POST" action="/job-portal/public/applications/shortlist_action.php" class="mb-0 flex-grow-1">
                                                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                    <button type="submit" class="btn company-btn company-btn-primary" onclick="return confirm('Shortlist this applicant?')">
                                                        <i class="fas fa-star"></i>
                                                        Shortlist
                                                    </button>
                                                </form>

                                                <!-- Hire Form -->
                                                <form method="POST" action="/job-portal/public/applications/hire_action.php" class="mb-0 flex-grow-1">
                                                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                    <button type="submit" class="btn company-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;" onclick="return confirm('Hire this applicant?')">
                                                        <i class="fas fa-check-double"></i>
                                                        Hire
                                                    </button>
                                                </form>

                                                <!-- Reject Form -->
                                                <form method="POST" action="/job-portal/public/applications/reject_action.php" class="mb-0 flex-grow-1"
                                                      onsubmit="return confirm('Reject this applicant? This action will notify them.')">
                                                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                                    <button type="submit" class="btn company-btn company-btn-accent">
                                                        <i class="fas fa-times"></i>
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Applicant view: no company actions -->
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>No Applications Yet</h3>
                    <p class="text-muted">You haven't received any applications for your job postings yet.</p>
                    <a href="/job-portal/public/jobs/add_job.php" class="btn company-btn-primary company-btn mt-3" style="width: auto;">
                        <i class="fas fa-plus me-2"></i>Post a New Job
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Function to toggle cover letter expansion
function toggleCoverLetter(appId) {
    const coverElement = document.getElementById('cover-' + appId);
    const button = coverElement.nextElementSibling;
    
    if (coverElement.classList.contains('expanded')) {
        coverElement.classList.remove('expanded');
        button.innerHTML = 'Read More <i class="fas fa-chevron-down"></i>';
    } else {
        coverElement.classList.add('expanded');
        button.innerHTML = 'Show Less <i class="fas fa-chevron-up"></i>';
    }
}

// Auto-add read more buttons for long cover letters
document.addEventListener('DOMContentLoaded', function() {
    const coverLetters = document.querySelectorAll('.cover-letter-text');
    coverLetters.forEach((cover, index) => {
        if (cover.scrollHeight > 80) {
            cover.style.maxHeight = '80px';
            const readMoreBtn = document.createElement('button');
            readMoreBtn.type = 'button';
            readMoreBtn.className = 'read-more-btn';
            readMoreBtn.innerHTML = 'Read More <i class="fas fa-chevron-down"></i>';
            readMoreBtn.onclick = function() {
                toggleCoverLetter(cover.id.split('-')[1]);
            };
            cover.parentNode.appendChild(readMoreBtn);
        }
    });
});
</script>

<?php include "../includes/footer.php"; ?>