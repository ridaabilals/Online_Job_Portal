<?php
session_start();
require_once __DIR__ . "/../src/db.php";  
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerConnect - Find Your Dream Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #1A535C;    /* 60% - Dominant (Dark Teal) */
            --color-secondary: #F7FFF7;  /* 30% - Secondary (Off-White) */
            --color-accent: #4ECDC4;     /* 10% - Accent (Teal) */
            --color-accent2: #FF6B6B;    /* Additional Accent (Coral) */
            --color-accent3: #FFE66D;    /* Additional Accent (Yellow) */
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: white;
        }
        
        /* ===== 60% PRIMARY COLOR AREAS ===== */
        .navbar {
            background: linear-gradient(135deg, var(--color-primary) 0%, #0F3A42 100%) !important;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(26, 83, 92, 0.3);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: white !important;
            letter-spacing: -0.5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 600;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            border-radius: 4px;
            padding: 0.5rem 1rem !important;
        }
        
        .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-link.active {
            background-color: rgba(78, 205, 196, 0.2);
            color: white !important;
        }
        
        footer {
            background: linear-gradient(135deg, var(--color-primary) 0%, #0F3A42 100%);
            color: white;
            padding: 3rem 0 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent3));
        }
        
        /* ===== 30% SECONDARY COLOR AREAS ===== */
        .stats-section {
            background-color: var(--color-secondary);
            padding: 4rem 0;
            position: relative;
        }
        
        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent3), var(--color-accent));
        }
        
        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            height: 100%;
            box-shadow: 0 10px 30px rgba(26, 83, 92, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(26, 83, 92, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent3));
        }
        
        .feature-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(26, 83, 92, 0.15);
        }
        
        .job-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 8px 25px rgba(26, 83, 92, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(26, 83, 92, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent3));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .job-card:hover::before {
            transform: scaleX(1);
        }
        
        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(26, 83, 92, 0.15);
        }
        
        /* ===== 10% ACCENT COLOR AREAS ===== */
        .btn-accent {
            background: linear-gradient(135deg, var(--color-accent) 0%, #3ABBB3 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 0.7rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.4);
        }
        
        .btn-accent:hover {
            background: linear-gradient(135deg, #3ABBB3 0%, var(--color-accent) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(78, 205, 196, 0.6);
            color: white;
        }
        
        .hero-btn {
            background: linear-gradient(135deg, var(--color-accent2) 0%, #FF5252 100%);
            color: white;
            font-weight: 700;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }
        
        .hero-btn:hover {
            background: linear-gradient(135deg, #FF5252 0%, var(--color-accent2) 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.6);
            color: white;
        }
        
        .view-details-btn {
            background: linear-gradient(135deg, var(--color-primary) 0%, #0F3A42 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1.5rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            box-shadow: 0 4px 15px rgba(26, 83, 92, 0.3);
        }
        
        .view-details-btn:hover {
            background: linear-gradient(135deg, #0F3A42 0%, var(--color-primary) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 83, 92, 0.4);
        }
        
        .feature-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--color-accent) 0%, #3ABBB3 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(78, 205, 196, 0.4);
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 25px rgba(78, 205, 196, 0.6);
        }
        
        /* ===== HERO SECTION ===== */
        .hero-section {
            background: linear-gradient(135deg, var(--color-primary) 0%, #0F3A42 100%);
            color: white;
            padding: 7rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(to top, var(--color-secondary), transparent);
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }
        
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        /* ===== TYPOGRAPHY & CONTENT ===== */
        .section-title {
            text-align: center;
            font-weight: 800;
            color: var(--color-primary);
            margin-bottom: 3rem;
            font-size: 2.5rem;
            position: relative;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 6px;
            background: linear-gradient(90deg, var(--color-accent2), var(--color-accent3));
            margin: 0.5rem auto;
            border-radius: 3px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .stats-section p {
            font-size: 1.1rem;
            color: #555;
            font-weight: 600;
        }
        
        .company-name {
            color: var(--color-primary);
            font-weight: 700;
            margin-bottom: 1.2rem;
            font-size: 1rem;
        }
        
        .job-meta {
            margin-bottom: 0.8rem;
            color: #555;
            font-size: 0.95rem;
        }
        
        .job-meta i {
            color: var(--color-accent);
            width: 20px;
            margin-right: 0.5rem;
        }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: linear-gradient(135deg, var(--color-secondary) 0%, #E8F5E8 100%);
            border-radius: 16px;
            border: 2px dashed rgba(26, 83, 92, 0.2);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--color-accent2);
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            color: var(--color-primary);
            margin-bottom: 1rem;
        }
        
        /* ===== FOOTER ===== */
        .footer-heading {
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            color: white;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.7rem;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--color-accent3);
            padding-left: 5px;
        }
        
        .copyright {
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            color: rgba(255,255,255,0.7);
        }
        
        /* ===== ADDITIONAL STYLING ===== */
        .highlight-badge {
            background: linear-gradient(135deg, var(--color-accent2) 0%, #FF5252 100%);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            position: absolute;
            top: 1rem;
            right: 1rem;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.4);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--color-accent2), var(--color-accent3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* ===== RESPONSIVE ADJUSTMENTS ===== */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1.1rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .feature-card, .job-card {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/navbar.php"; ?>

    <div class="container-fluid px-0">

        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content text-center text-white">
                <h1 class="fw-bold">Find Your <span class="gradient-text">Dream Career</span></h1>
                <p class="lead">Discover thousands of opportunities from top companies worldwide. Your next career move starts here.</p>
                <a href="jobs.php" class="btn hero-btn pulse-animation">
                    <i class="fas fa-search me-2"></i> Explore Opportunities
                </a>
                
                <!-- Floating shapes for visual interest -->
                <div class="floating-shape" style="width: 100px; height: 100px; top: 20%; left: 10%; animation-delay: 0s;"></div>
                <div class="floating-shape" style="width: 150px; height: 150px; bottom: 10%; right: 15%; animation-delay: 2s;"></div>
                <div class="floating-shape" style="width: 80px; height: 80px; top: 60%; left: 5%; animation-delay: 4s;"></div>
            </div>
        </div>

        <!-- Quick Stats Section -->
        <div class="row stats-section text-center">
            <div class="col-md-3 mb-4">
                <div class="stat-number">
                    <?php
                    $totalJobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM jobs WHERE status='approved'"))['count'];
                    echo $totalJobs ?: '0';
                    ?>
                </div>
                <p>Active Jobs</p>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-number">
                    <?php
                    $totalCompanies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM companies"))['count'];
                    echo $totalCompanies ?: '0';
                    ?>
                </div>
                <p>Partner Companies</p>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-number">
                    <?php
                    $totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
                    echo $totalUsers ?: '0';
                    ?>
                </div>
                <p>Job Seekers</p>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-number">24/7</div>
                <p>Support</p>
            </div>
        </div>

        <!-- Features Section -->
        <div class="container my-5 py-5">
            <h2 class="section-title">Why Choose <span class="gradient-text">CareerConnect</span>?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h4 class="fw-bold">Premium Opportunities</h4>
                        <p class="text-muted">Access exclusive job listings from top-tier companies and innovative startups.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h4 class="fw-bold">Quick Apply</h4>
                        <p class="text-muted">One-click applications with your saved profile and resume for faster submissions.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="fw-bold">Verified Companies</h4>
                        <p class="text-muted">All companies are thoroughly verified for your security and peace of mind.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Jobs Section -->
        <div class="container my-5 py-4">
            <h2 class="section-title">Featured <span class="gradient-text">Opportunities</span></h2>

            <div class="row">
                <?php
                // Fetch latest 6 jobs
                $query = "SELECT j.id, j.title, j.location, j.salary_range, j.job_type, c.name AS company_name
                          FROM jobs j
                          LEFT JOIN companies c ON j.company_id = c.id
                          WHERE j.status = 'approved'
                          ORDER BY j.id DESC
                          LIMIT 6";

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0):
                    while ($job = mysqli_fetch_assoc($result)):
                ?>

                <!-- Job Card -->
                <div class="col-xl-4 col-lg-6 mb-4">
                    <div class="job-card">
                        <span class="highlight-badge">Hiring Now</span>
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($job['title']); ?></h5>
                        <h6 class="company-name">
                            <i class="fas fa-building me-2"></i><?= htmlspecialchars($job['company_name']); ?>
                        </h6>

                        <div class="job-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($job['location']); ?></span>
                        </div>

                        <div class="job-meta">
                            <i class="fas fa-money-bill-wave"></i>
                            <span><?= htmlspecialchars($job['salary_range']); ?></span>
                        </div>

                        <div class="job-meta">
                            <i class="fas fa-clock"></i>
                            <span><?= htmlspecialchars($job['job_type']); ?></span>
                        </div>

                        <a href="job_view.php?id=<?= $job['id']; ?>" class="view-details-btn">
                            <i class="fas fa-eye me-2"></i> View Details
                        </a>
                    </div>
                </div>

                <?php endwhile; else: ?>

                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>No Jobs Available</h3>
                        <p class="text-muted">Check back later for new opportunities.</p>
                        <a href="jobs.php" class="btn view-details-btn mt-3" style="width: auto;">
                            Browse All Jobs
                        </a>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="container my-5 py-4">
            <div class="row">
                <div class="col-12 text-center">
                    <h3 class="mb-4 fw-bold">Ready to Start Your Journey?</h3>
                    <p class="mb-4 text-muted">Join thousands of professionals who found their dream jobs through CareerConnect</p>
                    <a href="jobs.php" class="btn hero-btn" style="width: auto; padding: 1rem 3rem;">
                        <i class="fas fa-search me-2"></i> Explore All Jobs
                    </a>
                </div>
            </div>
        </div>

    </div>

    <?php include "includes/footer.php"; ?>

  
</body>
</html>