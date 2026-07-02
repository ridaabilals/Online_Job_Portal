<?php
// public/job_view.php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT j.*, c.name as company_name
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.id = ?
");

if ($stmt === false) {
    $err = mysqli_error($conn);
    $safeId = (int)$id;
    $rawSql = "SELECT j.*, c.name as company_name FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.id = $safeId LIMIT 1";
    $rawRes = mysqli_query($conn, $rawSql);
    if ($rawRes && mysqli_num_rows($rawRes) > 0) {
        $job = mysqli_fetch_assoc($rawRes);
    } else {
        $eMsg = $err ?: mysqli_error($conn) ?: 'Unknown database error';
        echo "<div class='container mt-5 alert alert-danger'>Database error: " . htmlspecialchars($eMsg) . "</div>";
        exit;
    }
} else {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $job = $res ? $res->fetch_assoc() : null;
}

if (!$job) {
    echo "<div class='container mt-5 alert alert-danger'>Job not found</div>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1A535C;
            --primary-light: #4ECDC4;
            --background: #F7FFF7;
            --accent: #FF6B6B;
            --highlight: #FFE66D;
        }

        body {
            background-color: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .job-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .job-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            border: none;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            background: white;
            padding: 8px;
        }

        .job-title {
            color: var(--background);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .company-name {
            color: var(--primary-light);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .meta-badge {
            background: var(--primary-light);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 10px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .job-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--primary-light);
        }

        .job-section h5 {
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .apply-btn {
            background: var(--primary-light);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .apply-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }

        .info-card h6 {
            color: var(--primary-dark);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .info-card p {
            color: #495057;
            font-weight: 500;
            margin-bottom: 0;
        }

        .urgent-badge {
            background: var(--accent);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .highlight-box {
            background: var(--highlight);
            color: var(--primary-dark);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="job-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="job-title"><?= htmlspecialchars($job['title']) ?></h1>
                    <h4 class="company-name"><?= htmlspecialchars($job['company_name']) ?></h4>
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($job['location']) ?>
                        <?php if (!empty($job['application_deadline']) && strtotime($job['application_deadline']) < strtotime('+3 days')): ?>
                            <span class="urgent-badge ms-3">
                                <i class="fas fa-clock me-1"></i>Apply Soon!
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if (!empty($job['company_logo'])): ?>
                        <img src="<?= htmlspecialchars($job['company_logo']) ?>" alt="<?= htmlspecialchars($job['company_name']) ?>" class="company-logo">
                    <?php else: ?>
                        <div class="company-logo d-inline-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-building text-primary fa-2x"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Column - Job Details -->
            <div class="col-lg-8">
                <div class="job-card">
                    <!-- Job Meta -->
                    <div class="mb-4">
                        <span class="meta-badge">
                            <i class="fas fa-briefcase me-1"></i><?= htmlspecialchars($job['job_type']) ?>
                        </span>
                        <span class="meta-badge" style="background: var(--primary-dark);">
                            <i class="fas fa-money-bill-wave me-1"></i><?= htmlspecialchars($job['salary_range']) ?>
                        </span>
                        <?php if (!empty($job['category'])): ?>
                        <span class="meta-badge" style="background: var(--accent);">
                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($job['category']) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Job Description -->
                    <div class="job-section">
                        <h5><i class="fas fa-file-alt me-2"></i>Job Description</h5>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                    </div>

                    <!-- Requirements -->
                    <div class="job-section">
                        <h5><i class="fas fa-tasks me-2"></i>Requirements</h5>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                    </div>

                    <!-- Benefits -->
                    <?php if (!empty($job['benefits'])): ?>
                    <div class="job-section">
                        <h5><i class="fas fa-gift me-2"></i>Benefits & Perks</h5>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($job['benefits'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Apply Section -->
                    <div class="text-center mt-4 pt-4 border-top">
                        <?php if (isLoggedIn() && ($_SESSION['is_company'] ?? 0) == 0): ?>
                            <a href="applications/apply.php?job_id=<?= $job['id'] ?>" class="btn apply-btn btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Apply Now
                            </a>
                        <?php else: ?>
                            <div class="highlight-box">
                                <i class="fas fa-lock fa-2x mb-3"></i>
                                <h5>Login Required</h5>
                                <p class="mb-3">Please login as a job seeker to apply for this position</p>
                                <a href="login.php" class="btn" style="background: var(--primary-dark); color: white;">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Company Info -->
            <div class="col-lg-4">
                <!-- Quick Apply Notice -->
                <?php if (isLoggedIn() && ($_SESSION['is_company'] ?? 0) == 0): ?>
                <div class="highlight-box mb-4">
                    <i class="fas fa-bolt me-2"></i>
                    <strong>Quick Apply Available</strong>
                    <p class="mb-0 small">Your profile is ready for one-click application</p>
                </div>
                <?php endif; ?>

                <!-- Company Information -->
                <div class="job-card">
                    <h5 class="mb-3" style="color: var(--primary-dark);">
                        <i class="fas fa-building me-2"></i>Company Info
                    </h5>
                    
                    <div class="info-card">
                        <h6><i class="far fa-calendar me-2"></i>Posted Date</h6>
                        <p><?= date('F j, Y', strtotime($job['posted_at'])) ?></p>
                    </div>

                    <div class="info-card">
                        <h6><i class="fas fa-briefcase me-2"></i>Employment Type</h6>
                        <p><?= htmlspecialchars($job['job_type']) ?></p>
                    </div>

                    <div class="info-card">
                        <h6><i class="fas fa-money-bill-wave me-2"></i>Salary Range</h6>
                        <p><?= htmlspecialchars($job['salary_range']) ?></p>
                    </div>

                    <div class="info-card">
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Location</h6>
                        <p><?= htmlspecialchars($job['location']) ?></p>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include __DIR__ . '/includes/footer.php'; ?>