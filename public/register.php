<?php
// public/register.php
require_once __DIR__ . '/../src/db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $redirectPage = $_SESSION['role'] === "admin" ? "admin_dashboard.php" : 
                   ($_SESSION['is_company'] == 1 ? "company_dashboard.php" : "user_dashboard.php");
    header("Location: /job-portal/public/dashboard/" . $redirectPage);
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? ''; // ✅ PLAIN PASSWORD
    $full_name  = trim($_POST['full_name'] ?? '');
    $is_company = isset($_POST['is_company']) ? 1 : 0;
    $phone = trim($_POST['phone'] ?? '');

    // ✅ Validations
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username invalid (3-30 chars: letters, numbers, underscore)';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if (empty($errors)) {

        // ✅ NO HASHING — STORE PLAIN PASSWORD
        $plainPassword = $password;

        // ensure phone column exists
        $res = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone'");
        if (mysqli_num_rows($res) === 0) {
            mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL");
        }

        // ✅ Insert into users table (including optional phone)
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, full_name, is_company, phone) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssis", $username, $email, $plainPassword, $full_name, $is_company, $phone);

        if ($stmt->execute()) {

            // ✅ GET NEW USER ID
            $user_id = $conn->insert_id;

            // ✅ IF REGISTERED AS COMPANY → CREATE COMPANIES ROW
            if ($is_company == 1) {

                $company_name = !empty($full_name) ? $full_name : $username;

                $company_stmt = $conn->prepare(
                    "INSERT INTO companies (user_id, name) VALUES (?, ?)"
                );
                $company_stmt->bind_param("is", $user_id, $company_name);
                $company_stmt->execute();
            }

            header("Location: login.php?registered=1");
            exit;

        } else {
            if ($conn->errno == 1062) {
                $errors[] = "Username or Email already exists";
            } else {
                $errors[] = "Database Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CareerConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-dark: #1A535C;
        --primary-light: #4ECDC4;
        --background: #F7FFF7;
        --accent: #FF6B6B;
        --highlight: #FFE66D;
        --gradient: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
    }

    /* Register Page Specific Styles - Isolated */
    .register-page-wrapper {
        background: var(--gradient);
        min-height: 100vh;
        padding: 80px 20px 40px;
        position: relative;
        overflow: hidden;
    }

    .register-page-wrapper::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: rgba(78, 205, 196, 0.1);
        border-radius: 50%;
        transform: rotate(30deg);
    }

    .register-container {
        background: var(--background);
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.8);
        width: 100%;
        max-width: 500px;
        position: relative;
        z-index: 1;
        animation: slideUp 0.6s ease-out;
        margin: 0 auto;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .register-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .register-logo {
        width: 80px;
        height: 80px;
        background: var(--gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        color: var(--background);
        font-size: 2rem;
        box-shadow: 0 10px 20px rgba(26, 83, 92, 0.3);
    }

    .register-title {
        color: var(--primary-dark);
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .register-subtitle {
        color: var(--primary-dark);
        opacity: 0.8;
        font-size: 1rem;
    }

    .register-form-group {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .register-form-control {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1rem 1rem 3rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #fff;
        width: 100%;
        color: var(--primary-dark);
    }

    .register-form-control:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2);
        transform: translateY(-2px);
    }

    .register-form-control::placeholder {
        color: #a0aec0;
    }

    .register-form-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-dark);
        opacity: 0.6;
        z-index: 2;
        transition: all 0.3s ease;
    }

    .register-form-control:focus + .register-form-icon {
        color: var(--primary-light);
        opacity: 1;
    }

    .btn-register {
        background: var(--gradient);
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--background);
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 1rem;
        box-shadow: 0 10px 20px rgba(26, 83, 92, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-register::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-register:hover::before {
        left: 100%;
    }

    .btn-register:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(26, 83, 92, 0.4);
        color: var(--background);
    }

    .btn-register:active {
        transform: translateY(-1px);
    }

    .register-links {
        text-align: center;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e2e8f0;
    }

    .register-link {
        color: var(--primary-dark);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
    }

    .register-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: var(--accent);
        transition: width 0.3s ease;
    }

    .register-link:hover {
        color: var(--accent);
        text-decoration: none;
    }

    .register-link:hover::after {
        width: 100%;
    }

    .register-alert-danger {
        background: rgba(255, 107, 107, 0.1);
        border: none;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        color: var(--primary-dark);
        border-left: 4px solid var(--accent);
        margin-bottom: 1.5rem;
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .register-floating-shapes {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        overflow: hidden;
        z-index: 0;
    }

    .register-shape {
        position: absolute;
        background: rgba(247, 255, 247, 0.1);
        border-radius: 50%;
        animation: register-float 6s ease-in-out infinite;
    }

    .register-shape:nth-child(1) {
        width: 80px;
        height: 80px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }

    .register-shape:nth-child(2) {
        width: 120px;
        height: 120px;
        top: 60%;
        left: 80%;
        animation-delay: 2s;
    }

    .register-shape:nth-child(3) {
        width: 60px;
        height: 60px;
        top: 80%;
        left: 20%;
        animation-delay: 4s;
    }

    .register-shape:nth-child(4) {
        width: 100px;
        height: 100px;
        top: 40%;
        left: 85%;
        animation-delay: 1s;
    }

    .register-shape:nth-child(5) {
        width: 70px;
        height: 70px;
        top: 70%;
        left: 5%;
        animation-delay: 3s;
    }

    @keyframes register-float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }

    .register-checkbox-container {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        padding: 1rem;
        background: rgba(78, 205, 196, 0.1);
        border-radius: 12px;
        border: 2px solid rgba(78, 205, 196, 0.3);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .register-checkbox-container:hover {
        border-color: var(--primary-light);
        background: rgba(78, 205, 196, 0.2);
        transform: translateY(-2px);
    }

    .register-checkbox-container input[type="checkbox"] {
        margin-right: 1rem;
        transform: scale(1.2);
        accent-color: var(--primary-light);
    }

    .register-checkbox-label {
        font-weight: 600;
        color: var(--primary-dark);
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .register-form-row {
        display: flex;
        gap: 1rem;
    }

    .register-form-col {
        flex: 1;
    }

    .register-password-strength {
        margin-top: 0.5rem;
        font-size: 0.85rem;
    }

    .register-strength-bar {
        height: 4px;
        background: #e2e8f0;
        border-radius: 2px;
        margin-top: 0.25rem;
        overflow: hidden;
    }

    .register-strength-fill {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }

    .register-strength-weak {
        background: var(--accent);
        width: 33%;
    }

    .register-strength-medium {
        background: var(--highlight);
        width: 66%;
    }

    .register-strength-strong {
        background: var(--primary-light);
        width: 100%;
    }

    .register-features-box {
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: rgba(255, 230, 109, 0.2);
        border-radius: 12px;
        text-align: center;
        border-left: 4px solid var(--highlight);
        animation: register-pulse 2s infinite;
    }

    @keyframes register-pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 230, 109, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(255, 230, 109, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 230, 109, 0); }
    }

    .register-features-box small {
        color: var(--primary-dark);
        font-weight: 600;
    }

    .register-features-box i {
        color: var(--primary-dark);
        margin-right: 0.5rem;
    }

    .register-user-type-info {
        display: none;
        margin-top: 0.5rem;
        padding: 0.75rem;
        background: rgba(78, 205, 196, 0.1);
        border-radius: 8px;
        border-left: 3px solid var(--primary-light);
        animation: register-fadeIn 0.3s ease;
    }

    @keyframes register-fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .register-user-type-info.show {
        display: block;
    }

    .register-toggle-password {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--primary-dark);
        opacity: 0.6;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .register-toggle-password:hover {
        opacity: 1;
        color: var(--primary-light);
    }

    @media (max-width: 768px) {
        .register-container {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }
        
        .register-title {
            font-size: 1.75rem;
        }
        
        .register-form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .register-features-box {
            padding: 1rem;
        }
    }
    </style>
</head>
<body>
    <!-- Include Existing Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Register Page Content -->
    <div class="register-page-wrapper">
        <!-- Floating Background Shapes -->
        <div class="register-floating-shapes">
            <div class="register-shape"></div>
            <div class="register-shape"></div>
            <div class="register-shape"></div>
            <div class="register-shape"></div>
            <div class="register-shape"></div>
        </div>
        
        <div class="register-container">
            <!-- Header Section -->
            <div class="register-header">
                <div class="register-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="register-title">Join CareerConnect</h1>
                <p class="register-subtitle">Create your account and unlock amazing opportunities</p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="register-alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" id="registerForm">
                <div class="register-form-row">
                    <div class="register-form-col">
                        <div class="register-form-group">
                            <i class="fas fa-user register-form-icon"></i>
                            <input type="text" 
                                   name="username" 
                                   class="register-form-control" 
                                   placeholder="Username" 
                                   required
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="register-form-col">
                        <div class="register-form-group">
                            <i class="fas fa-id-card register-form-icon"></i>
                            <input type="text" 
                                   name="full_name" 
                                   class="register-form-control" 
                                   placeholder="Full Name" 
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="register-form-group">
                    <i class="fas fa-envelope register-form-icon"></i>
                    <input type="email" 
                           name="email" 
                           class="register-form-control" 
                           placeholder="Email Address" 
                           required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="register-form-group">
                    <i class="fas fa-phone register-form-icon"></i>
                    <input type="tel" 
                           name="phone" 
                           class="register-form-control" 
                           placeholder="Phone Number (Optional)" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="register-form-group">
                    <i class="fas fa-lock register-form-icon"></i>
                    <input type="password" 
                           name="password" 
                           class="register-form-control" 
                           placeholder="Password" 
                           required
                           minlength="6"
                           id="registerPasswordInput">
                    <button type="button" class="register-toggle-password" id="registerTogglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="register-password-strength">
                        <div id="registerPasswordStrength" class="register-strength-text text-muted"></div>
                        <div class="register-strength-bar">
                            <div id="registerStrengthFill" class="register-strength-fill"></div>
                        </div>
                    </div>
                </div>

                <!-- Company Registration Option -->
                <div class="register-checkbox-container" id="registerCompanyCheckboxContainer">
                    <input name="is_company" 
                           type="checkbox" 
                           class="form-check-input" 
                           id="registerIsCompany"
                           <?= isset($_POST['is_company']) ? 'checked' : '' ?>>
                    <label class="register-checkbox-label" for="registerIsCompany">
                        <i class="fas fa-building me-2"></i>
                        Register as a Company
                    </label>
                </div>

                <div class="register-user-type-info" id="registerCompanyInfo">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Company accounts can post jobs, manage applications, and build their employer brand.
                    </small>
                </div>

                <div class="register-user-type-info" id="registerJobSeekerInfo">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Job seeker accounts can apply for jobs, save favorites, and get matched with opportunities.
                    </small>
                </div>

                <button class="btn btn-register" type="submit">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                </button>
            </form>

            <!-- Additional Links -->
            <div class="register-links">
                <p class="mb-0">
                    Already have an account? 
                    <a href="/job-portal/public/login.php" class="register-link">Sign in here</a>
                </p>
            </div>

            <!-- Features Box -->
            <div class="register-features-box">
                <small>
                    <i class="fas fa-rocket me-1"></i>
                    <strong>Join 15,000+ professionals</strong> who found their dream jobs through CareerConnect
                </small>
            </div>
        </div>
    </div>

    <!-- Include Existing Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add interactive effects and password strength meter
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.register-form-control');
        const passwordInput = document.getElementById('registerPasswordInput');
        const togglePassword = document.getElementById('registerTogglePassword');
        const strengthText = document.getElementById('registerPasswordStrength');
        const strengthFill = document.getElementById('registerStrengthFill');
        const companyCheckbox = document.getElementById('registerIsCompany');
        const companyInfo = document.getElementById('registerCompanyInfo');
        const jobSeekerInfo = document.getElementById('registerJobSeekerInfo');
        
        // Add focus effect to inputs
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.register-form-icon').style.color = 'var(--primary-light)';
                this.parentElement.querySelector('.register-form-icon').style.opacity = '1';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('.register-form-icon').style.color = 'var(--primary-dark)';
                    this.parentElement.querySelector('.register-form-icon').style.opacity = '0.6';
                }
            });
        });
        
        // Toggle password visibility
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        }
        
        // Password strength meter
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let text = '';
                
                if (password.length >= 6) strength += 1;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
                if (password.match(/\d/)) strength += 1;
                if (password.match(/[^a-zA-Z\d]/)) strength += 1;
                
                // Update strength indicator
                strengthFill.className = 'register-strength-fill';
                if (password.length === 0) {
                    text = '';
                    strengthFill.style.width = '0%';
                } else if (password.length < 6) {
                    text = 'Too short';
                    strengthFill.classList.add('register-strength-weak');
                } else if (strength <= 2) {
                    text = 'Weak';
                    strengthFill.classList.add('register-strength-weak');
                } else if (strength === 3) {
                    text = 'Medium';
                    strengthFill.classList.add('register-strength-medium');
                } else {
                    text = 'Strong';
                    strengthFill.classList.add('register-strength-strong');
                }
                
                strengthText.textContent = text;
            });
        }
        
        // Company registration info
        if (companyCheckbox) {
            function updateUserTypeInfo() {
                if (companyCheckbox.checked) {
                    companyInfo.classList.add('show');
                    jobSeekerInfo.classList.remove('show');
                } else {
                    companyInfo.classList.remove('show');
                    jobSeekerInfo.classList.add('show');
                }
            }
            
            companyCheckbox.addEventListener('change', updateUserTypeInfo);
            updateUserTypeInfo(); // Initial call
        }
        
        // Form submission animation
        const form = document.getElementById('registerForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('.btn-register');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Creating Account...';
                    submitBtn.disabled = true;
                }
            });
        }
    });
    </script>
</body>
</html>