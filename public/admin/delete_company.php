<?php
session_start();
require_once __DIR__ . '/../../src/db.php';

/* ✅ FINAL ADMIN AUTH FIX — MATCHES YOUR ACTUAL LOGIN SYSTEM */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /job-portal/public/login.php");
    exit;
}

/* ✅ ONLY ALLOW POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request");
}

$company_id = intval($_POST['company_id'] ?? 0);

if ($company_id <= 0) {
    die("Invalid Company ID");
}

/* ✅ SAFE TRANSACTION DELETE */
    $conn->begin_transaction();

    // ✅ SAFE TRANSACTION DELETE - more robust checks + helpful flash messages

try {
    // get the owner of this company first
    $owner_stmt = $conn->prepare("SELECT user_id FROM companies WHERE id = ? LIMIT 1");
    if (!$owner_stmt) {
        throw new Exception("DB prepare failed (get owner): " . $conn->error);
    }
    $owner_stmt->bind_param("i", $company_id);
    if (!$owner_stmt->execute()) {
        throw new Exception("Owner query failed: " . $owner_stmt->error);
    }
    $owner_res = $owner_stmt->get_result();
    if ($owner_res->num_rows === 0) {
        throw new Exception("Company not found");
    }
    $owner_row = $owner_res->fetch_assoc();
    $owner_user_id = (int)$owner_row['user_id'];

    // look up owner role
    $owner_role = null;
    $user_stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    if ($user_stmt) {
        $user_stmt->bind_param("i", $owner_user_id);
        if ($user_stmt->execute()) {
            $user_res = $user_stmt->get_result();
            if ($user_res && $user_res->num_rows > 0) {
                $owner_role = $user_res->fetch_assoc()['role'] ?? null;
            }
        }
    }

    // count how many companies this user owns (before delete)
    $other_companies = 0;
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM companies WHERE user_id = ?");
    if ($count_stmt) {
        $count_stmt->bind_param("i", $owner_user_id);
        if ($count_stmt->execute()) {
            $cnt_res = $count_stmt->get_result();
            if ($cnt_res && ($r = $cnt_res->fetch_assoc())) {
                $other_companies = (int)$r['total'];
            }
        }
    }
    // ✅ Delete all jobs of this company first
    $job_stmt = $conn->prepare("DELETE FROM jobs WHERE company_id = ?");
        if (!$job_stmt) {
            throw new Exception("DB prepare failed (jobs): " . $conn->error);
        }
        $job_stmt->bind_param("i", $company_id);
        if (!$job_stmt->execute()) {
            throw new Exception("Job delete failed: " . $job_stmt->error);
        }

    // ✅ Delete the company
    $company_stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
        if (!$company_stmt) {
            throw new Exception("DB prepare failed (company): " . $conn->error);
        }
        $company_stmt->bind_param("i", $company_id);
        if (!$company_stmt->execute()) {
            throw new Exception("Company delete failed: " . $company_stmt->error);
        }

        // check that a row was actually deleted
        if ($conn->affected_rows === 0) {
            throw new Exception("Company not found or nothing deleted");
        }

    // We'll commit only after (possible) user deletion below so both user+company removals are atomic

    // Decide whether to also delete the owner user (only if the owner has no other companies
    // and the owner is not an admin, and owner is not the currently logged in admin)
    if ($owner_user_id > 0 && ($owner_role ?? '') !== 'admin') {
        if ($other_companies <= 1) {
            // Prevent deleting the currently-logged-in admin
            if ($owner_user_id === (int)($_SESSION['user_id'] ?? 0)) {
                $_SESSION['flash'] = "✅ Company deleted — owner account not removed (owner is your account).";
            } else {
                // remove any applications authored by that user
                $del_app_st = $conn->prepare("DELETE FROM applications WHERE user_id = ?");
                if ($del_app_st) {
                    $del_app_st->bind_param("i", $owner_user_id);
                    $del_app_st->execute();
                }

                $del_user_st = $conn->prepare("DELETE FROM users WHERE id = ?");
                if (!$del_user_st) {
                    // user deletion failed, but company was deleted — rollback to be safer
                    throw new Exception("DB prepare failed (delete user): " . $conn->error);
                }
                $del_user_st->bind_param("i", $owner_user_id);
                if (!$del_user_st->execute()) {
                    throw new Exception("Failed deleting owner user: " . $del_user_st->error);
                }

                $_SESSION['flash'] = "✅ Company and owner user deleted successfully.";
            }
        } else {
            $_SESSION['flash'] = "✅ Company deleted (owner has other companies, user kept).";
        }
    } else {
        if (empty($_SESSION['flash'])) {
            $_SESSION['flash'] = "✅ Company deleted, owner user not removed.";
        }
    }

    // complete transaction after all deletes
    $conn->commit();

    header("Location: /job-portal/public/admin/manage_companies.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();

    // provide user-visible flash and keep developer error in log
    $_SESSION['flash'] = "❌ Delete failed: " . htmlspecialchars($e->getMessage());

    // Consider logging to file or error log for deeper debugging
    error_log("[delete_company] " . $e->getMessage());

    header("Location: /job-portal/public/admin/manage_companies.php");
    exit;
}
?>
