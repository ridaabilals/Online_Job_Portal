<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

/* Company-only access (adjust if you use a different session shape) */
if (!isset($_SESSION['user_id']) || $_SESSION['is_company'] != 1) {
    header("Location: /job-portal/public/login.php");
    exit;
}

/* Get job id from query */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid job id.");
}
$job_id = (int)$_GET['id'];

/* Fetch existing job */
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Job not found.");
}
$job = $result->fetch_assoc();

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title        = trim($_POST['title'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $job_type     = trim($_POST['job_type'] ?? '');
    $salary_range = trim($_POST['salary_range'] ?? '');

    if ($title === '' || $location === '' || $job_type === '' || $salary_range === '') {
        $error = "All fields are required.";
    } else {
        $u = $conn->prepare("
            UPDATE jobs
            SET title = ?, location = ?, job_type = ?, salary_range = ?
            WHERE id = ?
        ");
        if (!$u) {
            $error = "Prepare failed: " . $conn->error;
        } else {
            $u->bind_param("ssssi", $title, $location, $job_type, $salary_range, $job_id);
            if ($u->execute()) {
                header("Location: /job-portal/public/dashboard/company_dashboard.php?updated=1");
                exit;
            } else {
                $error = "Update failed: " . $u->error;
            }
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container mt-5" style="max-width: 700px;">
    <h3 class="mb-4">Edit Job</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Job Title</label>
            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($job['title']) ?>">
        </div>

        <div class="mb-3">
            <label>Location</label>
            <input type="text" name="location" class="form-control" required value="<?= htmlspecialchars($job['location']) ?>">
        </div>

        <div class="mb-3">
            <label>Job Type</label>
            <select name="job_type" class="form-control" required>
                <option value="">Select</option>
                <option value="Full Time" <?= ($job['job_type'] == 'Full Time') ? 'selected' : '' ?>>Full Time</option>
                <option value="Part Time" <?= ($job['job_type'] == 'Part Time') ? 'selected' : '' ?>>Part Time</option>
                <option value="Internship" <?= ($job['job_type'] == 'Internship') ? 'selected' : '' ?>>Internship</option>
                <option value="Contract" <?= ($job['job_type'] == 'Contract') ? 'selected' : '' ?>>Contract</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Salary Range</label>
            <input type="text" name="salary_range" class="form-control" required value="<?= htmlspecialchars($job['salary_range']) ?>">
        </div>

        <button class="btn btn-primary w-100" type="submit">Update Job</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
