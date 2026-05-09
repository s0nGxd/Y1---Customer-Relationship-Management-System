<?php
/**
 * Authentication check
 * Ensures the user is logged in before accessing protected pages.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Helper function for role-based access control
function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        // Redirect to appropriate dashboard based on role
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: salesDash.php");
        }
        exit();
    }
}
?>
