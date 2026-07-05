<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

// Admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /job-portal/public/dashboard/admin_dashboard.php');
    exit;
}

$job_id = intval($_POST['job_id'] ?? 0);
if ($job_id <= 0) {
    $_SESSION['flash'] = 'Invalid job id';
    header('Location: /job-portal/public/dashboard/admin_dashboard.php');
    exit;
}

$stmt = $conn->prepare("UPDATE jobs SET status = 'rejected' WHERE id = ?");
if (!$stmt) {
    $_SESSION['flash'] = 'DB prepare failed: ' . $conn->error;
    header('Location: /job-portal/public/dashboard/admin_dashboard.php');
    exit;
}

$stmt->bind_param('i', $job_id);
if ($stmt->execute()) {
    $_SESSION['flash'] = '✅ Job rejected.';
} else {
    $_SESSION['flash'] = 'DB error: ' . $stmt->error;
}

header('Location: /job-portal/public/dashboard/admin_dashboard.php');
exit;
