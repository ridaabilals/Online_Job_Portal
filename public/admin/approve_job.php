<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

// ADMIN check should use same session format set at login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

// Handle approval via POST from Manage Jobs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = intval($_POST['job_id'] ?? 0);
    if ($job_id <= 0) {
        header("Location: manage_jobs.php?approved=0&error=invalid_id");
        exit;
    }

    $u = $conn->prepare("UPDATE jobs SET status = 'approved' WHERE id = ?");
    if ($u) {
        $u->bind_param("i", $job_id);
        $u->execute();
    }

    header("Location: manage_jobs.php?approved=1");
    exit;
}

// Handle approval via GET from this page
if (isset($_GET['id'])) {
    $job_id = intval($_GET['id']);
    if ($job_id > 0) {
        $u = $conn->prepare("UPDATE jobs SET status = 'approved' WHERE id = ?");
        if ($u) {
            $u->bind_param("i", $job_id);
            $u->execute();
        }
        header("Location: approve_job.php?approved=1");
        exit;
    }
}

// get all jobs for approval view
$result = $conn->query("
    SELECT jobs.*, companies.name 
    FROM jobs
    JOIN companies ON jobs.company_id = companies.id

    ORDER BY jobs.id DESC
    ");

if (!$result) {
    die("Query Failed: " . $conn->error);
}
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
    <h3>Admin Job Approval</h3>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Job</th>
                <th>Company</th>
                <th>Location</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>

        <?php while ($job = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($job['title']) ?></td>
                <td><?= htmlspecialchars($job['name']) ?></td>
                <td><?= htmlspecialchars($job['location']) ?></td>
                <td><?= $job['status'] ?></td>
                <td>
                    <?php if ($job['status'] === 'pending'): ?>
                        <a href="approve_job.php?id=<?= $job['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                        <form method="POST" action="reject_job_action.php" style="display: inline;">
                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this job?');">Reject</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>

        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>
