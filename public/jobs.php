<?php
session_start();
require_once __DIR__ . "/../src/db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs - CareerConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1A535C;    /* Deep teal - 60% */
            --primary-light: #4ECDC4;   /* Mint teal - 30% */
            --background: #F7FFF7;      /* Off-white - Background */
            --accent: #FF6B6B;          /* Coral red - 10% */
            --highlight: #FFE66D;       /* Sunny yellow - 10% */
            --gradient: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
            --card-gradient: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        /* Navbar Styles */
        .navbar-custom {
            background: var(--gradient);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--background) !important;
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            color: var(--background) !important;
        }

        .nav-link:hover {
            color: var(--highlight) !important;
            transform: translateY(-2px);
        }

        .btn-outline-light {
            color: var(--background);
            border-color: var(--background);
        }

        .btn-outline-light:hover {
            background: var(--highlight);
            color: var(--primary-dark);
            border-color: var(--highlight);
        }

        .badge {
            font-size: 0.6em;
        }

        /* Main Content Styles */
        body {
            background: var(--primary-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .jobs-page {
            min-height: 100vh;
            padding-top: 80px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            padding: 2rem 0;
        }

        .page-title {
            font-weight: 800;
            color: var(--background);
            font-size: 3.5rem;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 5px;
            background: var(--accent);
            border-radius: 3px;
        }

        .page-subtitle {
            font-weight: 500;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .filter-section {
            background: var(--card-gradient);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .filter-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .filter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient);
        }

        .filter-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .filter-title i {
            margin-right: 0.75rem;
            color: var(--accent);
        }

        .filter-form .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .filter-form .form-select,
        .filter-form .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .filter-form .form-select:focus,
        .filter-form .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(78, 205, 196, 0.25);
        }

        .filter-btn {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(26, 83, 92, 0.3);
        }

        .filter-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(26, 83, 92, 0.4);
            color: white;
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1c4a52 100%);
        }

        .clear-btn {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: block;
            text-align: center;
            margin-top: 0.5rem;
        }

        .clear-btn:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        .active-filters {
            background: var(--card-gradient);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--accent);
        }

        .filter-tag {
            background: var(--gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(26, 83, 92, 0.2);
        }

        .filter-tag i {
            margin-right: 0.25rem;
            font-size: 0.8rem;
        }

        .job-card {
            border: none;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            background: var(--card-gradient);
            border-left: 5px solid var(--primary-light);
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient);
        }

        .job-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .job-card .card-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
            line-height: 1.3;
        }

        .company-name {
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .company-name i {
            margin-right: 0.5rem;
            color: var(--primary-light);
        }

        .job-meta {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.95rem;
        }

        .job-meta i {
            margin-right: 0.75rem;
            width: 20px;
            color: var(--primary-light);
            font-size: 1.1rem;
        }

        .view-details-btn {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(26, 83, 92, 0.3);
        }

        .view-details-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(26, 83, 92, 0.4);
            color: white;
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1c4a52 100%);
        }

        .view-details-btn i {
            margin-right: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
            background: var(--card-gradient);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
            color: var(--primary-dark);
        }

        .empty-state h3 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .results-count {
            background: var(--card-gradient);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            font-weight: 600;
            color: var(--primary-dark);
            display: inline-block;
            border-left: 5px solid var(--accent);
        }

        .results-count .badge {
            background: var(--gradient);
            color: white;
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .jobs-grid {
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2.5rem;
            }
            
            .filter-section {
                padding: 1.5rem;
            }
            
            .job-card {
                padding: 1.5rem;
            }
        }

        .featured-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--accent);
            color: white;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.3);
        }

        .featured-badge i {
            margin-right: 0.25rem;
        }

        .job-type-tag {
            background: rgba(78, 205, 196, 0.2);
            color: var(--primary-dark);
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
            border: 1px solid rgba(78, 205, 196, 0.3);
        }

        .salary-highlight {
            color: var(--accent);
            font-weight: 700;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background: rgba(247, 255, 247, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 85%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 10%;
            animation-delay: 4s;
        }

        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 80%;
            animation-delay: 1s;
        }

        .shape:nth-child(5) {
            width: 70px;
            height: 70px;
            top: 70%;
            left: 70%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .stats-section {
            background: var(--card-gradient);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-top: 5px solid var(--accent);
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }

        
    </style>
</head>
<body>
    <?php include __DIR__ . "/includes/navbar.php"; ?>
    
    <div class="jobs-page">
        <!-- Floating Background Shapes -->
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
        
        <div class="container">

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Discover Your Dream Job</h1>
                <p class="page-subtitle">Browse through our curated list of career opportunities and find the perfect match for your skills and aspirations.</p>
            </div>

            <!-- Dynamic Stats Section -->
<?php
// Fetch real statistics from the database
$stats = [];

// Active Jobs Count (approved jobs)
$jobs_result = mysqli_query($conn, "SELECT COUNT(*) as total_jobs FROM jobs WHERE status = 'approved'");
$jobs_data = mysqli_fetch_assoc($jobs_result);
$stats['active_jobs'] = $jobs_data ? $jobs_data['total_jobs'] : 0;

// Companies Count
$companies_result = mysqli_query($conn, "SELECT COUNT(*) as total_companies FROM companies");
$companies_data = mysqli_fetch_assoc($companies_result);
$stats['companies'] = $companies_data ? $companies_data['total_companies'] : 0;

// Job Seekers Count (users who are not companies)
$job_seekers_result = mysqli_query($conn, "SELECT COUNT(*) as total_seekers FROM users WHERE is_company = 0");
$seekers_data = mysqli_fetch_assoc($job_seekers_result);
$stats['job_seekers'] = $seekers_data ? $seekers_data['total_seekers'] : 0;

// Success Rate Calculation
$success_rate = 95; // Default fallback

// Try to calculate success rate from applications if table exists
$applications_check = mysqli_query($conn, "SHOW TABLES LIKE 'applications'");
if (mysqli_num_rows($applications_check) > 0) {
    $applications_result = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_apps,
            SUM(CASE WHEN status = 'hired' OR status = 'accepted' THEN 1 ELSE 0 END) as successful_apps
        FROM applications
        WHERE status IS NOT NULL
    ");
    if ($applications_result && $app_data = mysqli_fetch_assoc($applications_result)) {
        if ($app_data['total_apps'] > 0) {
            $success_rate = round(($app_data['successful_apps'] / $app_data['total_apps']) * 100);
        }
    }
}

// Alternative: Use job fill rate if applications table doesn't exist or has no data
$jobs_filled_result = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_jobs,
        SUM(CASE WHEN status = 'filled' OR status = 'closed' THEN 1 ELSE 0 END) as filled_jobs
    FROM jobs
    WHERE status IS NOT NULL
");
if ($jobs_filled_result && $fill_data = mysqli_fetch_assoc($jobs_filled_result)) {
    if ($fill_data['total_jobs'] > 0 && $fill_data['filled_jobs'] > 0) {
        $fill_rate = round(($fill_data['filled_jobs'] / $fill_data['total_jobs']) * 100);
        // Use fill rate if it's meaningful
        if ($fill_rate > 0) {
            $success_rate = $fill_rate;
        }
    }
}

$stats['success_rate'] = $success_rate;
?>

<div class="stats-section">
    <div class="row text-center">
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['active_jobs']) ?>+</div>
                <div class="stat-label">Active Jobs</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['companies']) ?>+</div>
                <div class="stat-label">Companies</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['job_seekers']) ?>+</div>
                <div class="stat-label">Job Seekers</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-number"><?= $stats['success_rate'] ?>%</div>
                <div class="stat-label">Success Rate</div>
            </div>
        </div>
    </div>
</div>


            <!-- Filter Section -->
            <div class="filter-section">
                <h3 class="filter-title"><i class="fas fa-filter"></i>Refine Your Search</h3>
                <?php
                // Fetch distinct job types (categories) and locations for the filter form
                $types_result = mysqli_query($conn, "SELECT DISTINCT job_type FROM jobs WHERE job_type IS NOT NULL AND job_type != '' ORDER BY job_type ASC");
                $locations_result = mysqli_query($conn, "SELECT DISTINCT location FROM jobs WHERE location IS NOT NULL AND location != '' ORDER BY location ASC");

                // Read current filter values from GET so the form shows the chosen filters
                $filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';
                $filter_location = isset($_GET['location']) ? trim($_GET['location']) : '';
                $filter_salary_min = isset($_GET['salary_min']) ? trim($_GET['salary_min']) : '';
                $filter_salary_max = isset($_GET['salary_max']) ? trim($_GET['salary_max']) : '';
                ?>

                <form class="row g-3 filter-form" method="GET" action="jobs.php">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label"><i class="fas fa-briefcase me-1"></i>Job Category</label>
                        <select name="type" class="form-select">
                            <option value="">All Categories</option>
                            <?php while ($r = mysqli_fetch_assoc($types_result)): ?>
                                <option value="<?= htmlspecialchars($r['job_type']) ?>" <?= $r['job_type'] === $filter_type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['job_type']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Location</label>
                        <select name="location" class="form-select">
                            <option value="">All Locations</option>
                            <?php while ($r = mysqli_fetch_assoc($locations_result)): ?>
                                <option value="<?= htmlspecialchars($r['location']) ?>" <?= $r['location'] === $filter_location ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['location']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i>Min Salary</label>
                        <input type="number" name="salary_min" class="form-control" placeholder="e.g. 40000" value="<?= htmlspecialchars($filter_salary_min) ?>">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i>Max Salary</label>
                        <input type="number" name="salary_max" class="form-control" placeholder="e.g. 100000" value="<?= htmlspecialchars($filter_salary_max) ?>">
                    </div>

                    <div class="col-lg-2 col-md-12 d-flex align-items-end">
                        <div class="w-100">
                            <button class="btn filter-btn" type="submit">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="jobs.php" class="clear-btn">
                                <i class="fas fa-times me-1"></i>Clear All
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php
            // Build dynamic query based on selected filters (category / location).
            $conditions = [];
            $conditions[] = "j.status = 'approved'";

            if ($filter_type !== '') {
                $safe_type = mysqli_real_escape_string($conn, $filter_type);
                $conditions[] = "j.job_type = '" . $safe_type . "'";
            }

            if ($filter_location !== '') {
                $safe_location = mysqli_real_escape_string($conn, $filter_location);
                $conditions[] = "j.location = '" . $safe_location . "'";
            }

            $where_sql = '';
            if (count($conditions) > 0) {
                $where_sql = 'WHERE ' . implode(' AND ', $conditions);
            }

            $query = "SELECT j.id, j.title, j.location, j.salary_range, j.job_type, c.name AS company_name 
                      FROM jobs j
                      LEFT JOIN companies c ON j.company_id = c.id
                      " . $where_sql . "
                      ORDER BY j.id DESC";

            $result = mysqli_query($conn, $query);

            if (!$result) {
                die("SQL Error: " . mysqli_error($conn));
            }

            // Fetch rows into array and, if salary min/max were given, perform numeric filtering in PHP
            $jobs = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $jobs[] = $row;
            }

            // Helper: parse a salary_range like "40,000 - 60,000 PKR" into [min, max]
            function _parse_salary_range($txt) {
                if (!$txt) return [null, null];
                preg_match_all('/\d[\d,]*/', $txt, $m);
                if (empty($m[0])) return [null, null];
                $nums = array_map(function($s){ return (int)str_replace(',', '', $s); }, $m[0]);
                if (count($nums) == 1) return [$nums[0], $nums[0]];
                return [$nums[0], $nums[count($nums)-1]];
            }

            // Apply salary filter if provided
            if ($filter_salary_min !== '' || $filter_salary_max !== '') {
                $min = $filter_salary_min !== '' ? (int)$filter_salary_min : null;
                $max = $filter_salary_max !== '' ? (int)$filter_salary_max : null;

                if ($min !== null && $max !== null && $min > $max) {
                    $tmp = $min; $min = $max; $max = $tmp;
                }

                $filtered = [];
                foreach ($jobs as $j) {
                    list($jmin, $jmax) = _parse_salary_range($j['salary_range']);

                    if ($jmin === null && $jmax === null) {
                        $filtered[] = $j;
                        continue;
                    }

                    if ($min !== null && ($jmax === null || $jmax < $min)) {
                        continue;
                    }

                    if ($max !== null && ($jmin === null || $jmin > $max)) {
                        continue;
                    }

                    $filtered[] = $j;
                }

                $jobs = $filtered;
            }
            ?>

            <!-- Active Filters -->
            <?php if (!empty($filter_type) || !empty($filter_location) || !empty($filter_salary_min) || !empty($filter_salary_max)): ?>
                <div class="active-filters">
                    <h5 class="mb-3"><i class="fas fa-tags me-2"></i>Active Filters:</h5>
                    <?php
                        if ($filter_type !== '') echo '<span class="filter-tag"><i class="fas fa-briefcase"></i>Category: ' . htmlspecialchars($filter_type) . '</span>';
                        if ($filter_location !== '') echo '<span class="filter-tag"><i class="fas fa-map-marker-alt"></i>Location: ' . htmlspecialchars($filter_location) . '</span>';
                        if ($filter_salary_min !== '') echo '<span class="filter-tag"><i class="fas fa-money-bill-wave"></i>Salary ≥ ' . htmlspecialchars($filter_salary_min) . '</span>';
                        if ($filter_salary_max !== '') echo '<span class="filter-tag"><i class="fas fa-money-bill-wave"></i>Salary ≤ ' . htmlspecialchars($filter_salary_max) . '</span>';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Results Count -->
            <div class="results-count">
                <i class="fas fa-search me-2"></i>Found <span class="badge"><?= count($jobs) ?></span> job<?= count($jobs) !== 1 ? 's' : '' ?> matching your criteria
            </div>

            <!-- Jobs Grid -->
            <div class="jobs-grid">
                <div class="row">
                    <?php if (empty($jobs)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h3>No Jobs Found</h3>
                                <p class="text-muted">We couldn't find any jobs matching your current filters.</p>
                                <a href="jobs.php" class="btn view-details-btn" style="width: auto; padding: 0.75rem 2rem;">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($jobs as $job): ?>
                    <div class="col-xl-4 col-lg-6 mb-4">
                        <div class="job-card">
                            <!-- Featured Badge (you can add logic to show this for featured jobs) -->
                            <?php if (rand(0, 5) === 0): // Random featured badge for demo ?>
                                <span class="featured-badge"><i class="fas fa-star"></i>Featured</span>
                            <?php endif; ?>

                            <h5 class="card-title"><?= htmlspecialchars($job['title']); ?></h5>
                            
                            <div class="company-name">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($job['company_name']); ?>
                            </div>

                            <div class="job-meta">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($job['location']); ?></span>
                            </div>

                            <div class="job-meta">
                                <i class="fas fa-money-bill-wave"></i>
                                <span class="salary-highlight"><?= htmlspecialchars($job['salary_range']); ?></span>
                            </div>

                            <div class="job-meta">
                                <i class="fas fa-briefcase"></i>
                                <span><?= htmlspecialchars($job['job_type']); ?></span>
                            </div>

                            <span class="job-type-tag"><?= htmlspecialchars($job['job_type']); ?></span>

                            <a href="job_view.php?id=<?= $job['id']; ?>" class="btn view-details-btn">
                                <i class="fas fa-eye me-2"></i> View Details & Apply
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const jobCards = document.querySelectorAll('.job-card');
            
            // Add subtle animation on page load
            jobCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Add hover effect to filter buttons
            const filterBtn = document.querySelector('.filter-btn');
            if (filterBtn) {
                filterBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                filterBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-2px)';
                });
            }

            // Quick filter buttons functionality
            const quickFilterBtns = document.querySelectorAll('.quick-filter-btn');
            quickFilterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    quickFilterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Here you could add functionality to filter jobs by the selected quick filter
                    // For now, it's just visual
                });
            });
        });
    </script>

    <?php include "includes/footer.php"; ?>
</body>
</html>