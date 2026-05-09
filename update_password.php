<?php
require_once 'auth.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get form data
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $error = "All password fields are required.";
} elseif ($new_password !== $confirm_password) {
    $error = "New passwords do not match.";
} elseif (strlen($new_password) < 8) {
    $error = "Password must be at least 8 characters long.";
} else {
    // Get current password hash from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stored_hash = $user['password'];
        
        // Verify current password
        if (password_verify($current_password, $stored_hash)) {
            // Check if new password is different
            if (password_verify($new_password, $stored_hash)) {
                $error = "New password must be different from current password.";
            } else {
                // Hash the new password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $new_hash, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password updated successfully!";
                } else {
                    $error = "Error updating password: " . $conn->error;
                }
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        $error = "User not found.";
    }
}

$conn->close();

// Store message in session and redirect back
if (!empty($error)) {
    $_SESSION['profile_update_error'] = $error;
} elseif (!empty($success)) {
    $_SESSION['profile_update_success'] = $success;
}

header("Location: profile.php#security");
exit();
?>
