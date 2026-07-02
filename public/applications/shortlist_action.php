<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

// Only company users can shortlist
if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header('Location: /job-portal/public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['application_id'])) {
    header('Location: /job-portal/public/applications/my_applications.php');
    exit;
}

$application_id = (int)$_POST['application_id'];
$company_user_id = $_SESSION['user_id'];

/* Verify this company owns the job for this application */
$stmt = $conn->prepare(
    "SELECT a.id, a.user_id AS applicant_id, j.title AS job_title
     FROM applications a
     JOIN jobs j ON a.job_id = j.id
     JOIN companies c ON j.company_id = c.id
     WHERE a.id = ? AND c.user_id = ? LIMIT 1"
);
$stmt->bind_param("ii", $application_id, $company_user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // Not found or company doesn't own this application
    header('Location: /job-portal/public/applications/my_applications.php?error=unauthorized');
    exit;
}

// Update status to shortlisted and set updated_at
$update = $conn->prepare("UPDATE applications SET status = 'shortlisted', updated_at = NOW() WHERE id = ?");
if (!$update) {
    // prepare failed - capture error
    $err = urlencode($conn->error ?: 'prepare_failed');
    header('Location: /job-portal/public/applications/my_applications.php?error=update_prepare&msg=' . $err);
    exit;
}

$update->bind_param("i", $application_id);
if (!$update->execute()) {
    $err = urlencode($update->error ?: 'execute_failed');
    header('Location: /job-portal/public/applications/my_applications.php?error=update_exec&msg=' . $err);
    exit;
}

// ensure the row was actually updated (or at least the row was touched)
if ($update->affected_rows === 0) {
    // Could be already shortlisted or nothing changed — we'll attempt to verify the stored value.
}

// Re-read the application record to discover the effective status stored in DB
$checkApp = $conn->prepare("SELECT status FROM applications WHERE id = ? LIMIT 1");
if ($checkApp) {
    $checkApp->bind_param('i', $application_id);
    $checkApp->execute();
    $cur = $checkApp->get_result();
    $curRow = $cur ? $cur->fetch_assoc() : null;
    $actualStatus = $curRow['status'] ?? '';
} else {
    $actualStatus = '';
}

// If DB stored an empty or incompatible status (some schemas use 'shortlist' instead of 'shortlisted'),
// attempt a fallback update to the common alternative 'shortlist'. This catches ENUM mismatches.
if ($actualStatus !== 'shortlisted') {
    $alt = 'shortlist';
    if ($actualStatus !== $alt) {
        $altUpdate = $conn->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($altUpdate) {
            $altUpdate->bind_param('si', $alt, $application_id);
            $altUpdate->execute();
            // re-check
            $checkApp->execute();
            $cur = $checkApp->get_result();
            $curRow = $cur ? $cur->fetch_assoc() : null;
            $actualStatus = $curRow['status'] ?? '';
        }
    }
}

// If status is still empty or invalid, we likely hit an ENUM restriction — attempt a safe automatic fix:
if (empty($actualStatus)) {
    // Detect column type for `status`
    $dbName = $conn->real_escape_string($conn->query('SELECT DATABASE()')->fetch_row()[0]);
    $colQ = $conn->prepare("SELECT COLUMN_TYPE, DATA_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = 'applications' AND column_name = 'status' LIMIT 1");
    if ($colQ) {
        $colQ->bind_param('s', $dbName);
        $colQ->execute();
        $colRes = $colQ->get_result();
        $colInfo = $colRes ? $colRes->fetch_assoc() : null;
        $colType = $colInfo['COLUMN_TYPE'] ?? '';
        $dataType = $colInfo['DATA_TYPE'] ?? '';

        // Only alter if it's an enum (or any non-varchar) so we don't accidentally change desired schemas.
        if (stripos($colType, 'enum(') !== false || $dataType !== 'varchar') {
            // Try to ALTER table to make status VARCHAR(32) DEFAULT 'pending'
            $alter = "ALTER TABLE applications MODIFY status VARCHAR(32) DEFAULT 'pending'";
            $alterRes = @$conn->query($alter);

            if ($alterRes !== false) {
                // Attempt update again using preferred value
                $retry = $conn->prepare("UPDATE applications SET status = 'shortlisted', updated_at = NOW() WHERE id = ?");
                if ($retry) {
                    $retry->bind_param('i', $application_id);
                    $retry->execute();
                    // If update worked, update actualStatus so UI shows the right state
                    $checkApp->execute();
                    $cur = $checkApp->get_result();
                    $curRow = $cur ? $cur->fetch_assoc() : null;
                    $actualStatus = $curRow['status'] ?? '';
                }
            }
        }
    }
}

// Create notifications table if it doesn't exist (safe idempotent operation)
$createNotifs = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    application_id INT DEFAULT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createNotifs);

// Insert notification for the applicant so they see the update in dashboard
$row = $res->fetch_assoc();
$applicant_id = (int)$row['applicant_id'];
$job_title = $conn->real_escape_string($row['job_title'] ?? '');
$message = "Your application for '{$job_title}' was shortlisted by the employer.";

$ins = $conn->prepare("INSERT INTO notifications (user_id, application_id, type, message) VALUES (?, ?, 'shortlisted', ?)");
$ins->bind_param('iis', $applicant_id, $application_id, $message);
$ins->execute();

header('Location: /job-portal/public/applications/my_applications.php?shortlisted=1');
exit;

?>
