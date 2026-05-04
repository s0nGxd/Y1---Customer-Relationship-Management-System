<?php
ob_start(); // Start output buffering
session_start();
require 'db_connect.php';

// Check if user is logged in and submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    // Collect and sanitize input
    $user_role = $_SESSION['role'] ?? '';
    
    // Determine sales_rep_id based on role
    if ($user_role === 'admin') {
        // Admin can assign to any sales rep
        $sales_rep_id = $conn->real_escape_string($_POST['sales_rep_id'] ?? '');
    } else {
        // Sales rep can only assign to themselves
        $sales_rep_id = $_SESSION['user_id'];
    }
    
    $company = $conn->real_escape_string($_POST['company'] ?? '');
    $contact_first = $conn->real_escape_string($_POST['contact_first'] ?? '');
    $contact_last = $conn->real_escape_string($_POST['contact_last'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^\d+]/', '', $_POST['phone'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $name = trim("$contact_first $contact_last");

    // Validate inputs
    $errors = [];
    if (!is_numeric($sales_rep_id)) $errors[] = "Invalid Sales Rep";
    if (empty($company)) $errors[] = "Company name is required";
    if (empty($name)) $errors[] = "Contact name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

    if (empty($errors)) {
        try {
            // Check sales rep exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $check_stmt->bind_param("i", $sales_rep_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid sales representative");
            }

            // Insert customer
            $stmt = $conn->prepare("INSERT INTO customer 
                (sales_rep_id, name, company, email, phone_number, address)
                VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("isssss",
                $sales_rep_id,
                $name,
                $company,
                $email,
                $phone,
                $address
            );

            if ($stmt->execute()) {
                $_SESSION['success'] = "Customer added successfully!";
            } else {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }

    ob_end_clean(); // Clean any accidental output
    header("Location: customer.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>