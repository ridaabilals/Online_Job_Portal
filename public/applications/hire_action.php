<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

// Ensure user is logged in and is a company
if (!isset($_SESSION['user_id']) || ($_SESSION['is_company'] ?? 0) != 1) {
    header('Location: /job-portal/public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$app_id = (int)($_POST['application_id'] ?? 0);

if (!$app_id) {
    $_SESSION['flash'] = 'Invalid application ID.';
    header('Location: /job-portal/public/applications/my_applications.php');
    exit;
}

// Verify the company owns this application's job
$stmt = $conn->prepare("
    SELECT a.id FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.id = ? AND j.company_id = (SELECT id FROM companies WHERE user_id = ?)
");
$stmt->bind_param('ii', $app_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = 'Unauthorized: You cannot hire for this application.';
    header('Location: /job-portal/public/applications/my_applications.php?error=unauthorized');
    exit;
}

// Update application status to 'hired'
$update = $conn->prepare("UPDATE applications SET status = 'hired' WHERE id = ?");
$update->bind_param('i', $app_id);

if ($update->execute()) {
    header('Location: /job-portal/public/applications/my_applications.php?hired=1');
    exit;
} else {
    $_SESSION['flash'] = 'Failed to hire applicant. Please try again.';
    header('Location: /job-portal/public/applications/my_applications.php');
    exit;
}
?>
