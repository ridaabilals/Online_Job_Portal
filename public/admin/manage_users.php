<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

// ✅ Allow ONLY admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash messages helper (simple)
$flash = '';
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Fetch users using prepared statement
$stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC");
$stmt->execute();
$result = $stmt->get_result();

// Get user statistics
$totalUsers = $result->num_rows;
$adminUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'"))['count'];
$regularUsers = $totalUsers - $adminUsers;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
:root {
    --primary-dark: #1A535C;
    --primary-light: #4ECDC4;
    --background: #F7FFF7;
    --accent: #FF6B6B;
    --highlight: #FFE66D;
    --company-gradient: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
    --accent-gradient: linear-gradient(135deg, var(--accent) 0%, #FF8E8E 100%);
    --highlight-gradient: linear-gradient(135deg, var(--highlight) 0%, #FFEE9D 100%);
    --primary-light-gradient: linear-gradient(135deg, var(--primary-light) 0%, #6EF5ED 100%);
}

.admin-users-page {
    background: var(--background);
    min-height: 100vh;
    padding: 30px 0;
}

.page-header-admin {
    background: var(--company-gradient);
    color: white;
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
    position: relative;
    overflow: hidden;
}

.page-header-admin::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.stats-card-admin {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.stats-card-admin::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-light);
}

.stats-card-admin:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stats-card-admin.total { 
    background: var(--company-gradient); 
}
.stats-card-admin.admins { 
    background: var(--primary-light-gradient); 
}
.stats-card-admin.users { 
    background: linear-gradient(135deg, var(--primary-dark) 0%, #2a6d6c 100%); 
}

.users-table-container {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    border-top: 4px solid var(--primary-light);
}

.table-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.users-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.users-table thead th {
    background: var(--company-gradient);
    color: white;
    font-weight: 600;
    padding: 1rem 1.5rem;
    border: none;
    position: relative;
}

.users-table thead th:first-child {
    border-top-left-radius: 15px;
}

.users-table thead th:last-child {
    border-top-right-radius: 15px;
}

.users-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e9ecef;
}

.users-table tbody tr:hover {
    background: rgba(78, 205, 196, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.users-table tbody td {
    padding: 1.25rem 1.5rem;
    vertical-align: middle;
    border: none;
}

.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.role-admin {
    background: var(--primary-light-gradient);
    color: white;
}

.role-user {
    background: #e9ecef;
    color: #495057;
}

.admin-btn {
    border-radius: 25px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.admin-btn-warning {
    background: var(--highlight-gradient);
    color: #333;
}

.admin-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 230, 109, 0.4);
    color: #333;
}

.admin-btn-danger {
    background: var(--accent-gradient);
    color: white;
}

.admin-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
    color: white;
}

.flash-message {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-left: 4px solid var(--primary-light);
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
    color: var(--primary-dark);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--company-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--primary-dark);
}

.features-box {
    background: var(--background);
    border-radius: 15px;
    padding: 2rem;
    margin-top: 2rem;
    border: 2px dashed var(--highlight);
    text-align: center;
}

.features-box h4 {
    color: var(--primary-dark);
    margin-bottom: 1rem;
}

.features-box p {
    color: #666;
    margin-bottom: 1.5rem;
}

.protected-badge {
    color: var(--primary-light);
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .admin-users-page {
        padding: 15px 0;
    }
    
    .page-header-admin {
        padding: 1.5rem;
    }
    
    .users-table-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .admin-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="admin-users-page">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header-admin">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">👥 User Management</h1>
                    <p class="lead mb-0">Manage user accounts, roles, and permissions across the platform.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                        <small class="fw-bold" style="color: var(--primary-dark) !important;"><?= date('l, F j, Y') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert flash-message">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Notification</h5>
                        <?= htmlspecialchars($flash) ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card-admin total">
                    <div class="card-body">
                        <i class="fas fa-users mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Total Users</h6>
                        <h3><?= $totalUsers ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-admin admins">
                    <div class="card-body">
                        <i class="fas fa-crown mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Administrators</h6>
                        <h3><?= $adminUsers ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-admin users">
                    <div class="card-body">
                        <i class="fas fa-user mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Regular Users</h6>
                        <h3><?= $regularUsers ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <div class="table-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0" style="color: var(--primary-dark);">User Accounts</h3>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge fs-6" style="background: var(--primary-dark);"><?= $totalUsers ?> users</span>
                    </div>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th style="width: 120px;">Role</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $initial = strtoupper(substr($row['username'], 0, 1));
                            ?>
                                <tr>
                                    <td>
                                        <strong>#<?= htmlspecialchars($row['id']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?= $initial ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="color: var(--primary-dark);"><?= htmlspecialchars($row['username']) ?></div>
                                                <small class="text-muted">Joined <?= date('M j, Y', strtotime($row['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-envelope me-2 text-muted"></i>
                                            <?= htmlspecialchars($row['email']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?= $row['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                                            <i class="fas fa-<?= $row['role'] === 'admin' ? 'crown' : 'user' ?> me-1"></i>
                                            <?= htmlspecialchars(ucfirst($row['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['role'] !== 'admin'): ?>
                                            <div class="action-buttons">
                                                <!-- Make Admin: POST form with CSRF -->
                                                <form method="post" action="make_admin.php" class="mb-0">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <button type="submit" class="btn admin-btn admin-btn-warning">
                                                        <i class="fas fa-user-shield"></i>
                                                        Make Admin
                                                    </button>
                                                </form>

                                                <!-- Delete: POST form with CSRF -->
                                                <form method="post" action="delete_user.php" class="mb-0"
                                                      onsubmit="return confirm('Are you sure you want to delete this user and all related data? This action cannot be undone.');">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <button type="submit" class="btn admin-btn admin-btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="protected-badge">
                                                <i class="fas fa-shield-alt"></i>
                                                Protected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p class="text-muted">There are no users registered in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    const statsCards = document.querySelectorAll('.stats-card-admin');
    const tableRows = document.querySelectorAll('.users-table tbody tr');
    
    // Add subtle animation on page load for stats cards
    statsCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Add animation for table rows
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.5s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, (index + statsCards.length) * 50);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>