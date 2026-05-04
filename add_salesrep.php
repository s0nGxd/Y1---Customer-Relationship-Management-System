<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $firstName = trim($conn->real_escape_string($_POST['firstName']));
    $lastName = trim($conn->real_escape_string($_POST['lastName']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $username = trim($conn->real_escape_string($_POST['username']));
    $rawPassword = $_POST['password'];

    // Combine names
    $name = "$firstName $lastName";

    // Validate inputs
    $errors = [];
    if (empty($firstName) || empty($lastName)) $errors[] = "Name fields are required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($rawPassword)) $errors[] = "Password is required";

    // Check for existing username
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    $check->close();

    // Handle errors
    if (!empty($errors)) {
        http_response_code(400);
        die(implode("\n", $errors));
    }

    // Hash password
    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

    // Prepare and execute insert
    $stmt = $conn->prepare("INSERT INTO users 
        (username, password, role, name, email, phone_number)
        VALUES (?, ?, 'sales_rep', ?, ?, ?)");
    
    // Handle empty phone number
    $phone = empty($phone) ? null : $phone;
    
    $stmt->bind_param("sssss", 
        $username,
        $hashedPassword,
        $name,
        $email,
        $phone
    );

    if ($stmt->execute()) {
        echo "Sales representative added successfully!";
    } else {
        http_response_code(500);
        echo "Error: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    die("Method not allowed");
}