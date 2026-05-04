<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$username = $_POST['username'] ?? '';
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

// Validate inputs
if (empty($username) || empty($name) || empty($email)) {
    $_SESSION['profile_update_error'] = "Username, name, and email are required fields.";
    header("Location: profile.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['profile_update_error'] = "Invalid email format.";
    header("Location: profile.php");
    exit();
}

// Check if username or email already exists (excluding current user)
$stmt = $conn->prepare("SELECT user_id FROM Users WHERE (username = ? OR email = ?) AND user_id != ?");
$stmt->bind_param("ssi", $username, $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['profile_update_error'] = "Username or email already exists.";
    header("Location: profile.php");
    exit();
}

// Update user data
$stmt = $conn->prepare("UPDATE Users SET username = ?, name = ?, email = ?, phone_number = ? WHERE user_id = ?");
$stmt->bind_param("ssssi", $username, $name, $email, $phone, $user_id);

if ($stmt->execute()) {
    $_SESSION['profile_update_success'] = "Profile updated successfully!";
    
    // Update session data if needed
    $_SESSION['username'] = $username;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
} else {
    $_SESSION['profile_update_error'] = "Error updating profile: " . $conn->error;
}

$conn->close();
header("Location: profile.php");
exit();
?>