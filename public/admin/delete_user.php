<?php
session_start();
require_once __DIR__ . '/../../src/db.php'; 

/* ✅ ADMIN AUTH CHECK (MATCHES YOUR LOGIN SYSTEM) */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

/* ✅ CSRF TOKEN VALIDATION */
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF Token");
}

/* ✅ VALIDATE USER ID */
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid User ID");
}

$id = (int)$_POST['id'];

/* ✅ PREVENT SELF-DELETION */
if ($id === (int)$_SESSION['user_id']) {
    $_SESSION['flash'] = "❌ You cannot delete your own admin account.";
    header("Location: manage_users.php");
    exit;
}

/* ✅ DELETE USER */
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['flash'] = "✅ User deleted successfully!";
    header("Location: manage_users.php");
    exit;
} else {
    die("Database Error: Failed to delete user. " . $stmt->error);
}
?>
