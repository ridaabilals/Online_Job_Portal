<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /job-portal/public/login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/job-portal/public/dashboard/user_dashboard.php';

if ($id > 0) {
    // Ensure the notification belongs to this user
    $check = $conn->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ? LIMIT 1");
    $check->bind_param('ii', $id, $uid);
    $check->execute();
    $r = $check->get_result();

    if ($r && $r->num_rows > 0) {
        $upd = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $upd->bind_param('i', $id);
        $upd->execute();
    }
}

header('Location: ' . $redirect);
exit;

?>
