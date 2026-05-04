<?php
// Include database connection
require_once 'db_connect.php';

// Check if request is AJAX
header('Content-Type: application/json');

// Process the request based on action
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ADD SALES REP
    if ($action === 'add_sales_rep') {
        // Sanitize inputs
        $firstName = trim($conn->real_escape_string($_POST['firstName']));
        $lastName = trim($conn->real_escape_string($_POST['lastName']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = trim($conn->real_escape_string($_POST['phone']));
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

        // Check for existing username or email
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }
        $check->close();

        // Handle errors
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => implode(", ", $errors)
            ]);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

        // Prepare and execute insert
        $stmt = $conn->prepare("INSERT INTO users 
            (username, password, role, name, email, phone_number)
            VALUES (?, ?, 'sales_rep', ?, ?, ?)");
        
        $stmt->bind_param("sssss", 
            $username,
            $hashedPassword,
            $name,
            $email,
            $phone
        );

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Sales representative added successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Database error: " . $conn->error
            ]);
        }
        
        $stmt->close();
    } 
    
    // UPDATE SALES REP
    else if ($action === 'update_sales_rep') {
        // Extract the numeric ID from the repId (remove 'SR' prefix)
        $repIdFull = $_POST['repId'];
        $user_id = preg_replace('/[^0-9]/', '', $repIdFull);
        
        // Sanitize inputs
        $firstName = trim($conn->real_escape_string($_POST['firstName']));
        $lastName = trim($conn->real_escape_string($_POST['lastName']));
        $name = $firstName . ' ' . $lastName;
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = trim($conn->real_escape_string($_POST['phone']));
        $username = trim($conn->real_escape_string($_POST['username']));
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        try {
            // Check if username or email already exists for another user
            $checkQuery = "SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("sss", $username, $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username or email already exists for another user.'
                ]);
                exit;
            }
            
            // Update sales representative
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET 
                        username = ?, 
                        password = ?, 
                        name = ?, 
                        email = ?, 
                        phone_number = ? 
                        WHERE user_id = ? AND role = 'sales_rep'";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $username, $hashedPassword, $name, $email, $phone, $user_id);
            } else {
                // Update without changing password
                $sql = "UPDATE users SET 
                        username = ?, 
                        name = ?, 
                        email = ?, 
                        phone_number = ? 
                        WHERE user_id = ? AND role = 'sales_rep'";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $username, $name, $email, $phone, $user_id);
            }
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sales representative updated successfully.'
                ]);
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No action specified'
    ]);
}

$conn->close();