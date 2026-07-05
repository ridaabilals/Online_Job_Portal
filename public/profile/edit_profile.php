<?php
session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';

requireLogin();
$uid = currentUserId();
if (!$uid) {
    header('Location: /job-portal/public/login.php');
    exit;
}

// Ensure phone column exists
$res = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($email === '') {
        $msg = 'Email is required';
    } else {
        $u = $conn->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
        $u->bind_param('sssi', $full_name, $email, $phone, $uid);
        if ($u->execute()) {
            $_SESSION['flash'] = 'Profile updated successfully!';
            // update session copies
            if (isset($_SESSION['user'])) {
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone'] = $phone;
            }
            if (isset($_SESSION['username'])) {
                // nothing to update here unless username changed
            }
            header('Location: edit_profile.php');
            exit;
        } else {
            $msg = 'DB error: ' . htmlspecialchars($conn->error);
        }
    }
}

$stmt = $conn->prepare('SELECT username, full_name, email, phone FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1A535C;
            --primary-light: #4ECDC4;
            --background: #F7FFF7;
            --accent: #FF6B6B;
            --highlight: #FFE66D;
            --gradient: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
            --light-gradient: linear-gradient(135deg, var(--primary-light) 0%, #6bd4cc 100%);
            --accent-gradient: linear-gradient(135deg, var(--accent) 0%, #ff5252 100%);
            --highlight-gradient: linear-gradient(135deg, var(--highlight) 0%, #ffed80 100%);
        }

        body {
            background-color: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .edit-profile-page {
            min-height: 100vh;
            padding: 30px 0;
        }

        .page-header-profile {
            background: var(--gradient);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header-profile::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(78, 205, 196, 0.2);
            border-radius: 50%;
        }

        .profile-form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 2px solid rgba(26, 83, 92, 0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(247, 255, 247, 0.8) 0%, rgba(255, 255, 255, 0.9) 100%);
            border-radius: 15px;
            border-left: 4px solid var(--primary-light);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.1);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--light-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin-right: 1.5rem;
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .user-info h4 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .user-info p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }

        .form-section {
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .section-title i {
            font-size: 1.2rem;
            color: var(--primary-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-light);
            width: 20px;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
            background: white;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .required-field::after {
            content: '*';
            color: var(--accent);
            margin-left: 0.25rem;
        }

        .character-count {
            font-size: 0.8rem;
            color: var(--primary-dark);
            text-align: right;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .submit-btn {
            background: var(--light-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
            box-shadow: 0 8px 20px rgba(78, 205, 196, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(78, 205, 196, 0.4);
            color: white;
            background: var(--light-gradient);
        }

        .flash-message {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .flash-success {
            border-left: 4px solid var(--primary-light);
            background: linear-gradient(135deg, rgba(78, 205, 196, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .flash-danger {
            border-left: 4px solid var(--accent);
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
        }

        .input-with-icon .form-control {
            padding-left: 3rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-light);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
        }

        .form-tips {
            background: var(--highlight-gradient);
            border: 2px solid var(--highlight);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .form-tips h6 {
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }

        .form-tips ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--primary-dark);
        }

        .form-tips li {
            margin-bottom: 0.5rem;
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .progress-step {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(26, 83, 92, 0.1);
        }

        @media (max-width: 768px) {
            .edit-profile-page {
                padding: 15px 0;
            }
            
            .page-header-profile {
                padding: 1.5rem;
            }
            
            .profile-form-container {
                padding: 1.5rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="edit-profile-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Page Header -->
                    <div class="page-header-profile">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-2"><i class="fas fa-user-edit me-2 floating"></i>Edit Profile</h1>
                                <p class="lead mb-0">Update your personal information and keep your profile current.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                                    <small class="fw-bold" style="color: var(--primary-dark);">Profile Settings</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if (!empty($msg)): ?>
                        <div class="alert flash-message flash-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem; color: var(--accent);"></i>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1">Update Failed</h5>
                                    <?= htmlspecialchars($msg) ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['flash'])): ?>
                        <div class="alert flash-message flash-success">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3" style="font-size: 1.5rem; color: var(--primary-light);"></i>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1">Success!</h5>
                                    <?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Form Container -->
                    <div class="profile-form-container">
                        <!-- User Header -->
                        <div class="profile-header">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <h4><?= htmlspecialchars($user['full_name'] ?? $user['username'] ?? 'User') ?></h4>
                                <p>Member since: <?= date('M Y') ?> • Last updated: <?= date('M j, Y') ?></p>
                            </div>
                        </div>

                        <!-- Profile Statistics -->
                        <div class="profile-stats">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php 
                                    $appCount = mysqli_fetch_assoc(mysqli_query($conn, 
                                        "SELECT COUNT(*) as count FROM applications WHERE user_id = $uid"
                                    ))['count'];
                                    echo $appCount;
                                    ?>
                                </div>
                                <div class="stat-label">Applications</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php 
                                    $shortCount = mysqli_fetch_assoc(mysqli_query($conn, 
                                        "SELECT COUNT(*) as count FROM applications WHERE user_id = $uid AND status IN ('shortlisted', 'shortlist')"
                                    ))['count'];
                                    echo $shortCount;
                                    ?>
                                </div>
                                <div class="stat-label">Shortlisted</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php 
                                    $activeCount = mysqli_fetch_assoc(mysqli_query($conn, 
                                        "SELECT COUNT(*) as count FROM applications WHERE user_id = $uid AND status NOT IN ('rejected', 'reject')"
                                    ))['count'];
                                    echo $activeCount;
                                    ?>
                                </div>
                                <div class="stat-label">Active</div>
                            </div>
                        </div>

                        <form method="POST" id="profileForm">
                            <!-- Personal Information Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user-circle"></i>
                                    Personal Information
                                </h3>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-signature"></i>
                                        Full Name
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                                               placeholder="Enter your full name" 
                                               maxlength="100">
                                    </div>
                                    <div class="character-count" id="nameCount"><?= strlen($user['full_name'] ?? '') ?>/100 characters</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required-field">
                                                <i class="fas fa-envelope"></i>
                                                Email Address
                                            </label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-at"></i>
                                                <input type="email" name="email" class="form-control" 
                                                       required 
                                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                                       placeholder="your.email@example.com" 
                                                       maxlength="255">
                                            </div>
                                            <div class="character-count" id="emailCount"><?= strlen($user['email'] ?? '') ?>/255 characters</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-phone"></i>
                                                Phone Number
                                            </label>
                                            <div class="input-with-icon">
                                                <i class="fas fa-mobile-alt"></i>
                                                <input type="text" name="phone" class="form-control" 
                                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                                       placeholder="+1 (555) 123-4567" 
                                                       maxlength="50">
                                            </div>
                                            <div class="character-count" id="phoneCount"><?= strlen($user['phone'] ?? '') ?>/50 characters</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Information Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-shield-alt"></i>
                                    Account Information
                                </h3>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-tag"></i>
                                        Username
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user"></i>
                                        <input type="text" class="form-control" 
                                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                               disabled 
                                               style="background: #f3f4f6; cursor: not-allowed;">
                                    </div>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                            </div>

                            <!-- Form Tips -->
                            <div class="form-tips">
                                <h6>
                                    <i class="fas fa-lightbulb"></i>
                                    Profile Tips
                                </h6>
                                <ul>
                                    <li>Keep your contact information up-to-date for employers</li>
                                    <li>Use a professional email address</li>
                                    <li>Add your phone number for faster communication</li>
                                    <li>Ensure your full name matches your resume</li>
                                    <li>Regularly update your profile for better opportunities</li>
                                </ul>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn submit-btn">
                                <i class="fas fa-save"></i>
                                Update Profile
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>