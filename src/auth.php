<?php
// start session if not already started (avoid duplicate session_start warnings)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  Require Login
function requireLogin() {
    // accept both legacy $_SESSION['user'] array and newer flat keys
    if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
        header("Location: /job-portal/public/login.php");
        exit;
    }
}

// return whether user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['user']);
}

// canonical helper to get current user id (supports both session shapes)
function currentUserId() {
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($_SESSION['user']['id'])) return (int)$_SESSION['user']['id'];
    return null;
}

// Require ADMIN
function requireAdmin() {
    requireLogin();

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        echo "<h3 style='color:red; text-align:center; margin-top:20px;'>Access Denied: Admin Only</h3>";
        exit;
    }
}

//  Require COMPANY
function requireCompany() {
    requireLogin();

    if (!isset($_SESSION['user']['is_company']) || $_SESSION['user']['is_company'] != 1) {
        echo "<h3 style='color:red; text-align:center; margin-top:20px;'>Access Denied: Company Only</h3>";
        exit;
    }
}

//  Require NORMAL USER (Job Seeker)
function requireUser() {
    requireLogin();

    if (!isset($_SESSION['user']['is_company']) || $_SESSION['user']['is_company'] != 0) {
        echo "<h3 style='color:red; text-align:center; margin-top:20px;'>Access Denied: Users Only</h3>";
        exit;
    }
}
