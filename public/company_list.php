<?php
// public/company_list.php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
requireLogin();

$sql = "SELECT c.*, u.username FROM companies c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC";
$res = $conn->query($sql);
if (!$res) {
  die("Query Failed: " . mysqli_error($conn));
}
include __DIR__ . '/includes/header.php';
?>
<div class="container mt-4">
  <h2>Companies</h2>
  <?php if(!empty($_GET['created'])): ?><div class="alert alert-success">Company created.</div><?php endif; ?>
  <table class="table table-striped">
    <thead><tr><th>ID</th><th>Name</th><th>Owner</th><th>Website</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= htmlspecialchars($row['website']) ?></td>
          <td>
            <a class="btn btn-sm btn-primary" href="/company_view.php?id=<?= $row['id'] ?>">View</a>
            <?php if($row['user_id'] == currentUserId() || ($_SESSION['role'] ?? '') === 'admin'): ?>
              <a class="btn btn-sm btn-secondary" href="/company_edit.php?id=<?= $row['id'] ?>">Edit</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
