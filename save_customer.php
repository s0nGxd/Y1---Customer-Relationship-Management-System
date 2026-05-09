<?php
require_once 'auth.php';

// Ensure user has appropriate permissions
if ($userRole !== 'admin' && $userRole !== 'sales_rep') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Check what action is being performed
    $action = $_POST['action'] ?? '';

    // Handle add customer
    if ($action === 'add_customer') {
        // Get form data
        $company = $_POST['company'] ?? '';
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $name = trim($firstName . ' ' . $lastName); // Combine first and last name
        $email = $_POST['email'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $address = $_POST['address'] ?? '';
        
        // Determine sales rep ID based on role
        $sales_rep_id = 0;
        if ($userRole === 'admin') {
            // Admin can assign to any sales rep
            $sales_rep_id = isset($_POST['sales_rep_id']) && !empty($_POST['sales_rep_id']) ? 
                $_POST['sales_rep_id'] : 0;
        } else if ($userRole === 'sales_rep') {
            // Sales rep can only assign to themselves
            $sales_rep_id = $userId;
        }
        
        // Validate sales rep ID
        if (empty($sales_rep_id)) {
            echo json_encode(['success' => false, 'message' => 'Sales representative must be selected']);
            exit;
        }
        
        // Insert customer into database
        $stmt = $conn->prepare("INSERT INTO customer (company, name, email, phone_number, address, sales_rep_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $company, $name, $email, $phone_number, $address, $sales_rep_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding customer: ' . $stmt->error]);
        }
        $stmt->close();
    }
    
    // Handle update customer
    else if ($action === 'update_customer') {
        // Get form data
        $custId = $_POST['custId'] ?? '';
        $company = $_POST['company'] ?? '';
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $name = trim($firstName . ' ' . $lastName); // Combine first and last name
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        
        // Handle sales rep reassignment for admin
        $sales_rep_id = null;
        if ($userRole === 'admin' && isset($_POST['sales_rep_id'])) {
            $sales_rep_id = $_POST['sales_rep_id'];
        }
        
        // Check permissions to edit this customer
        $canEdit = false;
        if ($userRole === 'admin') {
            $canEdit = true;
        } else if ($userRole === 'sales_rep') {
            // Check if customer belongs to this sales rep
            $checkStmt = $conn->prepare("SELECT customer_id FROM customer WHERE customer_id = ? AND sales_rep_id = ?");
            $checkStmt->bind_param('ii', $custId, $userId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            if ($result->num_rows > 0) {
                $canEdit = true;
            }
            $checkStmt->close();
        }
        
        if (!$canEdit) {
            echo json_encode(['success' => false, 'message' => 'Not authorized to edit this customer']);
            exit;
        }
        
        // Update customer in database
        if ($sales_rep_id !== null && $userRole === 'admin') {
            // Admin can reassign sales rep
            $stmt = $conn->prepare("UPDATE customer SET company = ?, name = ?, email = ?, phone_number = ?, address = ?, sales_rep_id = ? WHERE customer_id = ?");
            $stmt->bind_param('sssssii', $company, $name, $email, $phone, $address, $sales_rep_id, $custId);
        } else {
            // Regular update without changing sales rep
            $stmt = $conn->prepare("UPDATE customer SET company = ?, name = ?, email = ?, phone_number = ?, address = ? WHERE customer_id = ?");
            $stmt->bind_param('sssssi', $company, $name, $email, $phone, $address, $custId);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating customer: ' . $stmt->error]);
        }
        $stmt->close();
    }
    
    // Handle delete customer (if needed)
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>