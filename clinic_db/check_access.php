<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Ensures user is logged in and has allowed role(s)
 * @param array $roles Roles allowed to access this page
 */
function checkAccess(array $roles = [])
{
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }

    if (!empty($roles) && !in_array($_SESSION['user']['role'], $roles)) {
        echo "<script>alert('Access denied!'); window.location='login.php';</script>";
        exit;
    }
}
?>
