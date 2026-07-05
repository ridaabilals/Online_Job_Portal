<?php
session_start();
require_once __DIR__ . '/../../src/db.php'; 

/* ✅ ADMIN AUTH CHECK (MATCHES YOUR LOGIN SYSTEM) */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

/* ✅ CSRF SECURITY CHECK */
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF Token");
}

/* ✅ VALIDATE USER ID */
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid User ID");
}

$id = (int)$_POST['id'];

/* ✅ PROMOTE USER TO ADMIN */
$stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['flash'] = "✅ User promoted to admin successfully!";
    header("Location: manage_users.php");
    exit;
} else {
    die("Database Error: " . $stmt->error);
}
?>
