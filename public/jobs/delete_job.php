<?php
session_start();
require_once __DIR__ . "/../../src/db.php";

if (!isset($_GET['id'])) {
    die("Job ID missing.");
}

$id = $_GET['id'];

$sql = "DELETE FROM jobs WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

// ✅ CORRECT REDIRECT PATH
header("Location: ../dashboard/company_dashboard.php");
exit;
