<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_jobs.php");
    exit;
}

$job_id = intval($_POST['job_id'] ?? 0);

if ($job_id <= 0) {
    header("Location: manage_jobs.php?deleted=0&error=invalid_id");
    exit;
}

$stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();

header("Location: manage_jobs.php?deleted=1");
exit;
