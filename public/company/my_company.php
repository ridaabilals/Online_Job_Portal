<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
    <h2>Company Dashboard</h2>

    <div class="row mt-4">
        <div class="col-md-4">
            <a href="/job-portal/public/jobs/add_job.php" class="btn btn-primary w-100 mb-3">
                ➕ Post Job
            </a>
        </div>

        <div class="col-md-4">
            <a href="/job-portal/public/admin/manage_jobs.php" class="btn btn-dark w-100 mb-3">
                📋 Manage My Jobs
            </a>
        </div>

        <div class="col-md-4">
            <a href="/job-portal/public/applications/my_applications.php" class="btn btn-success w-100 mb-3">
                👥 View Applications
            </a>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
