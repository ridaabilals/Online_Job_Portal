<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
/* ✅ ENHANCED THEME Navbar Custom Styling */
.navbar {
    background: linear-gradient(135deg, #1A535C 0%, #0F3A42 100%);
    padding: 10px 0;
    box-shadow: 0 6px 25px rgba(26, 83, 92, 0.4);
    backdrop-filter: blur(15px);
    border-bottom: 1px solid rgba(78, 205, 196, 0.2);
    transition: all 0.3s ease;
}

.navbar.scrolled {
    padding: 8px 0;
    box-shadow: 0 8px 30px rgba(26, 83, 92, 0.5);
}

.navbar-brand {
    font-size: 1.8rem;
    letter-spacing: 0.8px;
    font-weight: 800;
    background: linear-gradient(135deg, #F7FFF7 0%, #4ECDC4 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 2px 10px rgba(78, 205, 196, 0.3);
    transition: all 0.4s ease;
    position: relative;
    display: inline-flex;
    align-items: center;
}

.navbar-brand::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #FF6B6B, #FFE66D);
    border-radius: 2px;
    transition: width 0.5s ease;
}

.navbar-brand:hover::after {
    width: 100%;
}

.navbar-brand:hover {
    transform: translateY(-2px);
    text-shadow: 0 4px 15px rgba(78, 205, 196, 0.5);
}

.navbar-brand .fa-heart {
    color: #FF6B6B;
    text-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
    margin-right: 8px;
    transition: all 0.3s ease;
}

.navbar-brand:hover .fa-heart {
    transform: scale(1.2) rotate(10deg);
    color: #FFE66D;
}

/* One-time float animation for logo */
@keyframes oneTimeFloat {
    0% { 
        transform: translateY(0) scale(1);
        opacity: 0.7;
    }
    25% { 
        transform: translateY(-8px) scale(1.05);
        opacity: 1;
    }
    50% { 
        transform: translateY(-12px) scale(1.1);
        opacity: 1;
    }
    75% { 
        transform: translateY(-6px) scale(1.05);
        opacity: 1;
    }
    100% { 
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.animated-logo {
    animation: oneTimeFloat 2s ease-in-out 1;
    display: inline-block;
}

.navbar-nav .nav-link {
    color: rgba(247, 255, 247, 0.9) !important;
    font-weight: 600;
    margin: 0 5px;
    transition: all 0.3s ease;
    border-radius: 25px;
    padding: 10px 18px !important;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
}

.navbar-nav .nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(78, 205, 196, 0.4), transparent);
    transition: left 0.6s;
}

.navbar-nav .nav-link:hover::before {
    left: 100%;
}

.navbar-nav .nav-link:hover {
    color: #F7FFF7 !important;
    background: rgba(78, 205, 196, 0.2);
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(78, 205, 196, 0.3);
}

.navbar-nav .nav-link i {
    font-size: 1.1rem;
    margin-right: 8px;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover i {
    transform: scale(1.2);
    color: #FFE66D;
}

.navbar-toggler {
    border: 1px solid rgba(247, 255, 247, 0.4);
    padding: 6px 10px;
    transition: all 0.3s ease;
}

.navbar-toggler:hover {
    background: rgba(78, 205, 196, 0.2);
    transform: scale(1.05);
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28247, 255, 247, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

.btn-nav {
    border-radius: 25px;
    font-weight: 700;
    padding: 10px 22px;
    transition: all 0.4s ease;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-nav::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.7s;
}

.btn-nav:hover::before {
    left: 100%;
}

.login-btn {
    background: rgba(78, 205, 196, 0.25);
    color: #F7FFF7 !important;
    border: 2px solid rgba(78, 205, 196, 0.6);
    box-shadow: 0 4px 15px rgba(78, 205, 196, 0.3);
}

.login-btn:hover {
    background: rgba(78, 205, 196, 0.4);
    color: #F7FFF7 !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(78, 205, 196, 0.5);
    border-color: rgba(78, 205, 196, 0.8);
}

.register-btn {
    background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
    color: #F7FFF7 !important;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.5);
    border: 2px solid rgba(255, 107, 107, 0.4);
}

.register-btn:hover {
    background: linear-gradient(135deg, #FF5252 0%, #FF6B6B 100%);
    color: #F7FFF7 !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.7);
    border-color: rgba(255, 107, 107, 0.6);
}

.logout-btn {
    background: rgba(247, 255, 247, 0.95);
    color: #1A535C !important;
    box-shadow: 0 4px 15px rgba(247, 255, 247, 0.3);
    font-weight: 600;
}

.logout-btn:hover {
    background: #F7FFF7;
    color: #0F3A42 !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(247, 255, 247, 0.4);
}

/* User welcome text */
.welcome-text {
    color: rgba(247, 255, 247, 0.95);
    font-weight: 600;
    margin-right: 15px;
    font-size: 1rem;
    display: flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: 20px;
    background: rgba(78, 205, 196, 0.15);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(78, 205, 196, 0.2);
}

.welcome-text .fa-hand-sparkles {
    color: #FFE66D;
    margin-right: 8px;
    text-shadow: 0 0 8px rgba(255, 230, 109, 0.5);
}

/* Notification badge */
.notification-badge {
    background: linear-gradient(135deg, #FFE66D, #FFD166);
    color: #8B7500;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: -5px;
    right: -5px;
    box-shadow: 0 2px 8px rgba(255, 230, 109, 0.5);
}

/* Dropdown menu styling */
.dropdown-menu {
    background: #F7FFF7;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(78, 205, 196, 0.3);
    border-radius: 15px;
    box-shadow: 0 12px 35px rgba(26, 83, 92, 0.25);
    overflow: hidden;
    padding: 8px;
    z-index: 1050;
}

.dropdown-item {
    color: #1A535C;
    font-weight: 600;
    transition: all 0.3s ease;
    border-radius: 10px;
    margin: 3px 0;
    padding: 10px 15px;
    display: flex;
    align-items: center;
}

.dropdown-item i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, rgba(78, 205, 196, 0.15) 0%, rgba(255, 230, 109, 0.15) 100%);
    color: #1A535C;
    transform: translateX(8px);
    box-shadow: 0 4px 12px rgba(26, 83, 92, 0.1);
}

.dropdown-item:hover i {
    transform: scale(1.2);
    color: #FF6B6B;
}

.dropdown-divider {
    border-color: rgba(78, 205, 196, 0.3);
    margin: 8px 0;
}

/* Mobile responsiveness */
@media (max-width: 991px) {
    .navbar-nav .nav-link {
        margin: 6px 0;
        text-align: center;
        justify-content: center;
    }
    
    .btn-nav {
        margin: 10px 0;
        width: 100%;
        text-align: center;
        justify-content: center;
    }
    
    .welcome-text {
        text-align: center;
        margin: 12px 0;
        display: flex;
        justify-content: center;
    }
    
    .dropdown-menu {
        text-align: center;
    }
}

/* Pulse effect for notifications */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

.notification-badge {
    animation: pulse 2s infinite;
}

/* Glass morphism effect for active nav items */
.navbar-nav .nav-link.active {
    background: rgba(78, 205, 196, 0.25);
    backdrop-filter: blur(10px);
    color: #F7FFF7 !important;
    box-shadow: 0 4px 15px rgba(78, 205, 196, 0.4);
    border: 1px solid rgba(78, 205, 196, 0.3);
}

.navbar-nav .nav-link.active::before {
    display: none;
}

/* Enhanced dropdown toggle */
.nav-link.dropdown-toggle::after {
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.nav-link.dropdown-toggle.show::after {
    transform: rotate(180deg);
}

/* Ripple effect styles */
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: scale(0);
    animation: ripple-animation 0.6s linear;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Animation completed state */
.animation-completed {
    animation: none !important;
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm fixed-top" id="mainNavbar">
  <div class="container">

    <!-- ✅ ENHANCED LOGO WITH ONE-TIME ANIMATION -->
    <a class="navbar-brand fw-bold" href="/job-portal/public/index.php" id="careerConnectLogo">
        <span class="animated-logo">
            <i class="fa-solid fa-heart me-2"></i>CareerConnect
        </span>
    </a>

    <!-- ✅ TOGGLER -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- ✅ MENU -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link" href="/job-portal/public/jobs.php">
            <i class="fa-solid fa-magnifying-glass me-2"></i> Find Jobs
          </a>
        </li>

        <!-- ✅ USER LOGGED IN -->
        <?php if (isset($_SESSION['user_id'])): ?>

            <!-- Welcome message (safe) -->
            <?php
                // Build a safe display name from session values — avoid undefined index notices
                $displayName = 'User';
                if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                    $displayName = $_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? $displayName;
                }
                if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
                    $displayName = $_SESSION['username'];
                }
            ?>
            <li class="nav-item">
                <span class="welcome-text">
                    <i class="fa-solid fa-hand-sparkles me-1"></i>
                    Welcome, <?= htmlspecialchars($displayName) ?>!
                </span>
            </li>

            <!-- ✅ ADMIN -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                  <a class="nav-link" href="/job-portal/public/dashboard/admin_dashboard.php">
                    <i class="fa-solid fa-crown me-2"></i> Admin Dashboard
                  </a>
                </li>

            <!-- ✅ COMPANY -->
            <?php elseif ($_SESSION['is_company'] == 1): ?>
                <li class="nav-item">
                  <a class="nav-link" href="/job-portal/public/dashboard/company_dashboard.php">
                    <i class="fa-solid fa-building me-2"></i> Company Dashboard
                  </a>
                </li>

                <!-- Applications (company only) -->
                <li class="nav-item position-relative">
                    <a class="nav-link" href="/job-portal/public/applications/my_applications.php">
                        <i class="fa-solid fa-file-lines me-2"></i> Applications
                        <span class="notification-badge"><?= isset($_SESSION['user_id']) ? (is_numeric($totalApps ?? null) ? $totalApps : '0') : '' ?></span>
                    </a>
                </li>

            <!-- ✅ NORMAL USER -->
            <?php else: ?>
                <li class="nav-item">
                  <a class="nav-link" href="/job-portal/public/dashboard/user_dashboard.php">
                    <i class="fa-solid fa-user me-2"></i> My Dashboard
                  </a>
                </li>
                
                <!-- Applications for normal users (if needed) -->
                <!-- <li class="nav-item position-relative">
                    <a class="nav-link" href="/job-portal/public/applications/my_applications.php">
                        <i class="fa-solid fa-file-lines me-2"></i> My Applications
                    </a>
                </li> -->
            <?php endif; ?>

            <!-- Profile dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" data-bs-target="#profileDropdown">
                    <i class="fa-solid fa-user-circle me-2"></i> Profile
                </a>
                <ul id="profileDropdown" class="dropdown-menu">
                    <li><a class="dropdown-item" href="/job-portal/public/profile/edit_profile.php">
                        <i class="fa-solid fa-user me-2"></i> My Profile
                    </a></li>
                    <li><a class="dropdown-item" href="/job-portal/public/profile/edit_profile.php">
                        <i class="fa-solid fa-gear me-2"></i> Settings
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item logout-btn" href="/job-portal/public/logout.php">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </a></li>
                </ul>
            </li>

        <!-- ✅ NOT LOGGED IN -->
        <?php else: ?>

            <li class="nav-item">
              <a class="nav-link login-btn" href="/job-portal/public/login.php">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Login
              </a>
            </li>

            <li class="nav-item ms-2">
              <a class="btn btn-nav register-btn" href="/job-portal/public/register.php">
                <i class="fa-solid fa-user-plus me-2"></i> Get Started
              </a>
            </li>

        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<!-- ✅ FIXED NAVBAR SPACING -->
<div style="height: 75px;"></div>

<!-- NOTE: scripts were simplified and made safe; they load Popper/Bootstrap only when needed and make dropdowns/active-links more robust -->
<script>
(function(){
    // tiny helper to dynamically load external scripts only if needed
    function loadScript(src, integrity, crossorigin) {
        return new Promise(function(resolve, reject) {
            // avoid duplicate loads
            if ([...document.scripts].some(s => s.src && s.src.includes(src))) return resolve();
            const s = document.createElement('script');
            s.src = src;
            if (integrity) s.integrity = integrity;
            if (crossorigin) s.crossOrigin = crossorigin;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    // if bootstrap isn't already present, load Popper then Bootstrap (safe, async)
    function ensureBootstrap() {
        if (typeof bootstrap === 'undefined') {
            return loadScript(
                "https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js",
                "sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r",
                "anonymous"
            ).then(function() {
                return loadScript(
                    "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js",
                    "sha384-fbbOQedDUMZZ5KreZpsbe1LCZPVmfTnH7ois6mU1QK+m14rQ1l2bGBq41eYeM/fS",
                    "anonymous"
                );
            }).catch(function(){ /* fail silently and keep fallback behavior */ });
        }
        return Promise.resolve();
    }

    function initNavbar() {
        const navbar = document.getElementById('mainNavbar');
        const logo = document.getElementById('careerConnectLogo');

        // scroll effect (unchanged)
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // animation end handling
        if (logo) {
            logo.addEventListener('animationend', function() {
                this.classList.add('animation-completed');
            });
        }

        // Robust active link logic — compare pathname (ignore query & hash)
        const currentPath = window.location.pathname.replace(/\/+$/, ''); // strip trailing slashes
        const menuItems = document.querySelectorAll('.nav-link[href]');
        menuItems.forEach(item => {
            try {
                const url = new URL(item.href, window.location.origin);
                const hrefPath = url.pathname.replace(/\/+$/, '');
                // match exact path OR endsWith to handle index.php vs directory
                if (
                    currentPath === hrefPath ||
                    currentPath.endsWith(hrefPath) ||
                    hrefPath.endsWith(currentPath)
                ) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            } catch (err) {
                // ignore invalid URLs
            }
        });

        // Ripple effect: exclude dropdown toggles to avoid interfering with dropdowns
        const interactiveElements = document.querySelectorAll('.btn-nav, .nav-link:not(.dropdown-toggle)');
        interactiveElements.forEach(element => {
            element.addEventListener('click', function(e) {
                // don't create ripple for right-click / special keys
                if (e.button !== 0) return;
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');

                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px;`;
                // ensure positioning
                if (getComputedStyle(this).position === 'static') {
                    this.style.position = 'relative';
                }
                this.style.overflow = 'hidden';
                this.appendChild(ripple);

                setTimeout(() => ripple.remove(), 600);
            }, {passive: true});
        });

        // Dropdown handling:
        // - Prefer Bootstrap's built-in behavior when available.
        // - Provide a simple fallback when Bootstrap isn't loaded.
        const toggles = document.querySelectorAll('.dropdown-toggle');
        toggles.forEach(toggle => {
            // fallback behaviour only if bootstrap is NOT present
            toggle.addEventListener('click', function(e) {
                if (typeof bootstrap !== 'undefined') {
                    // allow Bootstrap to handle it
                    return;
                }
                e.preventDefault();
                const menu = this.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    menu.classList.toggle('show');
                }
            });
        });

        // Close dropdowns when clicking outside or pressing Escape — but don't close when clicking inside
        document.addEventListener('click', function(e) {
            if (e.target.closest('.dropdown')) return; // clicking inside a dropdown: ignore
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
            }
        });
    }

    // ensure bootstrap scripts (if needed) then init — fallback to init even if loading fails
    // wait for DOM, then ensure bootstrap & init
    document.addEventListener('DOMContentLoaded', function() {
        // initialize nav behaviors regardless
        initNavbar();

        // Helper: initialize Bootstrap dropdown instances when bootstrap is available
        function initBootstrapDropdowns() {
            if (typeof bootstrap === 'undefined') return false;
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                try {
                    if (!toggle.dataset.bsInitialized) {
                        // Create a Bootstrap Dropdown instance so the native behavior works everywhere
                        new bootstrap.Dropdown(toggle);
                        toggle.dataset.bsInitialized = 'true';
                    }
                } catch (e) {
                    // ignore
                }
            });
            // Remove manual fallback handler when bootstrap is active
            document.removeEventListener('click', manualDropdownHandler);
            return true;
        }

        // Manual document-level fallback dropdown toggling (used only when Bootstrap isn't present).
        function manualDropdownHandler(e) {
            const toggle = e.target.closest('.dropdown-toggle');
            if (!toggle) return;
            e.preventDefault();
            const menu = toggle.nextElementSibling;
            if (!menu || !menu.classList.contains('dropdown-menu')) return;
            // Toggle .show on the menu (and set aria-expanded)
            menu.classList.toggle('show');
            const expanded = menu.classList.contains('show');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        // Try to ensure Bootstrap is loaded; if not, add fallback handler. If loaded later, initBootstrapDropdowns will remove fallback.
        ensureBootstrap().then(() => {
            // Bootstrap available — init them
            initBootstrapDropdowns();
        }).catch(() => {
            // Bootstrap might not load — attach a single fallback click handler
            document.removeEventListener('click', manualDropdownHandler);
            document.addEventListener('click', manualDropdownHandler);
        });

        // In case Bootstrap is added dynamically later (rare), poll briefly and initialize dropdowns once
        let pollCount = 0;
        const pollInterval = setInterval(() => {
            if (initBootstrapDropdowns() || ++pollCount > 10) {
                clearInterval(pollInterval);
            }
        }, 300);
    });
 })();
</script>