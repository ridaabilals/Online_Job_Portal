<?php
session_start();
require_once __DIR__ . "/../src/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password!";
    } else {
        // ✅ Fetch user from users table
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // ✅ USERNAME NOT FOUND
            if ($result->num_rows === 0) {
                $error = "Invalid username or password!";
            } else {
                $user = $result->fetch_assoc();

                // ✅ PASSWORD VERIFICATION - Consider using password_verify() for hashed passwords
                if ($password !== $user['password']) {
                    $error = "Invalid username or password!";
                } else {
                    // ✅ ✅ ✅ SUCCESSFUL LOGIN

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];        // admin | user
                    $_SESSION['is_company'] = (int)$user['is_company']; // 0 | 1

                    // Backwards-compatibility
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'is_company' => (int)$user['is_company']
                    ];

                    // ✅ ✅ ✅ CORRECT DASHBOARD REDIRECTION
                    if ($user['role'] === "admin") {
                        header("Location: /job-portal/public/dashboard/admin_dashboard.php");
                    } elseif ($user['is_company'] == 1) {
                        header("Location: /job-portal/public/dashboard/company_dashboard.php");
                    } else {
                        header("Location: /job-portal/public/dashboard/user_dashboard.php");
                    }
                    exit;
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1A535C;
            --primary-light: #4ECDC4;
            --background: #F7FFF7;
            --accent: #FF6B6B;
            --highlight: #FFE66D;
        }

        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #0F3A42 100%);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--highlight) !important;
        }

        .login-page {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #0F3A42 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--background);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            margin-top: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            font-size: 3rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
            border: 2px solid #ddd;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(78, 205, 196, 0.25);
        }

        .btn-login {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            width: 100%;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #ff5252;
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .login-links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
 <?php include "includes/navbar.php"; ?>
    <!-- Login Section -->
    <div class="login-page">
        <div class="login-container">
            <!-- Header Section -->
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h2 class="mb-3">Welcome Back</h2>
                <p class="text-muted">Sign in to continue your journey</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" 
                               name="username" 
                               class="form-control" 
                               placeholder="Enter your username" 
                               required
                               autocomplete="username"
                               id="username"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password" 
                               required
                               autocomplete="current-password"
                               id="password">
                        <button type="button" class="input-group-text password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-login mt-3" type="submit" id="submitBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In
                </button>
            </form>

            <!-- Additional Links -->
            <div class="login-links">
                <p class="mb-2">
                    Don't have an account? 
                    <a href="/job-portal/public/register.php" class="text-decoration-none">Create one here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('password');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('loginForm');
            
            // Password toggle functionality
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Form submission
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Signing In...';
            });
        });
    </script>
    <?php include "includes/footer.php"; ?>
</body>
</html>
 