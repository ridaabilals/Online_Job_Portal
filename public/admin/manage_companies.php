<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

// ✅ Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

// ✅ Correct query using REAL column names
$sql = "SELECT id, user_id, name, website, industry, description, created_at FROM companies";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

// Get company statistics
$totalCompanies = mysqli_num_rows($result);
$industriesResult = mysqli_query($conn, "SELECT COUNT(DISTINCT industry) as count FROM companies WHERE industry IS NOT NULL AND industry != ''");
$industryCount = mysqli_fetch_assoc($industriesResult)['count'];

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

.admin-companies-page {
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
.stats-card-admin.industries { 
    background: var(--primary-light-gradient); 
}
.stats-card-admin.active { 
    background: linear-gradient(135deg, var(--primary-dark) 0%, #2a6d6c 100%); 
}

.companies-table-container {
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

.companies-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.companies-table thead th {
    background: var(--company-gradient);
    color: white;
    font-weight: 600;
    padding: 1rem 1.5rem;
    border: none;
    position: relative;
}

.companies-table thead th:first-child {
    border-top-left-radius: 15px;
}

.companies-table thead th:last-child {
    border-top-right-radius: 15px;
}

.companies-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e9ecef;
}

.companies-table tbody tr:hover {
    background: rgba(78, 205, 196, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.companies-table tbody td {
    padding: 1.25rem 1.5rem;
    vertical-align: middle;
    border: none;
}

.company-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-weight: 500;
    font-size: 0.8rem;
    display: inline-block;
}

.industry-badge {
    background: var(--company-gradient);
    color: white;
}

.website-badge {
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

.company-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: var(--company-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
    margin-right: 1rem;
}

.company-info {
    display: flex;
    align-items: center;
}

.company-details {
    display: flex;
    flex-direction: column;
}

.company-name {
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 0.25rem;
}

.company-description {
    color: #666;
    font-size: 0.85rem;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.user-id-badge {
    background: #e9ecef;
    color: #495057;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
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

.website-link {
    color: var(--primary-dark);
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.website-link:hover {
    color: var(--primary-light);
    text-decoration: underline;
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

@media (max-width: 768px) {
    .admin-companies-page {
        padding: 15px 0;
    }
    
    .page-header-admin {
        padding: 1.5rem;
    }
    
    .companies-table-container {
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
    
    .companies-table {
        min-width: 800px;
    }
    
    .company-info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .company-avatar {
        margin-bottom: 0.5rem;
        margin-right: 0;
    }
}
</style>

<div class="admin-companies-page">
    <div class="container">

        <!-- Page Header -->
        <div class="page-header-admin">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">🏢 Company Management</h1>
                    <p class="lead mb-0">Manage all registered companies and their profiles on the platform.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                        <small class="fw-bold" style="color: var(--primary-dark) !important;"><?= date('l, F j, Y') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert flash-message">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1">Notification</h5>
                        <?= htmlspecialchars($_SESSION['flash']) ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card-admin total">
                    <div class="card-body">
                        <i class="fas fa-building mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Total Companies</h6>
                        <h3><?= $totalCompanies ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-admin industries">
                    <div class="card-body">
                        <i class="fas fa-industry mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Industries</h6>
                        <h3><?= $industryCount ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card-admin active">
                    <div class="card-body">
                        <i class="fas fa-chart-line mb-3" style="font-size: 2rem; opacity: 0.9;"></i>
                        <h6>Active Profiles</h6>
                        <h3><?= $totalCompanies ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Companies Table -->
        <div class="companies-table-container">
            <div class="table-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-0" style="color: var(--primary-dark);">Registered Companies</h3>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge fs-6" style="background: var(--primary-dark);"><?= $totalCompanies ?> companies</span>
                    </div>
                </div>
            </div>

            <?php if ($totalCompanies > 0): ?>
                <div class="table-responsive">
                    <table class="companies-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Company Details</th>
                                <th style="width: 150px;">Industry</th>
                                <th style="width: 120px;">Website</th>
                                <th style="width: 100px;">User ID</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($result, 0); // Reset result pointer
                            while ($row = mysqli_fetch_assoc($result)): 
                                $initial = strtoupper(substr($row['name'], 0, 2));
                            ?>
                                <tr>
                                    <td>
                                        <strong>#<?= htmlspecialchars($row['id']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="company-info">
                                            <div class="company-avatar">
                                                <?= $initial ?>
                                            </div>
                                            <div class="company-details">
                                                <div class="company-name"><?= htmlspecialchars($row['name']) ?></div>
                                                <?php if (!empty($row['description'])): ?>
                                                    <div class="company-description" title="<?= htmlspecialchars($row['description']) ?>">
                                                        <?= htmlspecialchars(substr($row['description'], 0, 50)) ?>...
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['industry'])): ?>
                                            <span class="company-badge industry-badge">
                                                <?= htmlspecialchars($row['industry']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['website'])): ?>
                                            <a href="<?= htmlspecialchars($row['website']) ?>" 
                                               class="website-link" 
                                               target="_blank"
                                               title="<?= htmlspecialchars($row['website']) ?>">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                Visit
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="user-id-badge">
                                            #<?= htmlspecialchars($row['user_id']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- ✅ DELETE BUTTON -->
                                            <form method="POST"
                                                  action="/job-portal/public/admin/delete_company.php"
                                                  onsubmit="return confirm('Are you sure you want to delete this company and all associated data? This action cannot be undone.');"
                                                  class="mb-0">
                                                <input type="hidden" name="company_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn admin-btn admin-btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <h3>No Companies Found</h3>
                    <p class="text-muted">There are no companies registered in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>

        

    </div>
</div>

<script>
// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
    const statsCards = document.querySelectorAll('.stats-card-admin');
    const tableRows = document.querySelectorAll('.companies-table tbody tr');
    
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