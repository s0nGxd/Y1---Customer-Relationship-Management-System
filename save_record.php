<?php
require_once 'auth.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => 'Unknown error occurred'
);

try {
    // Check what action is being performed
    $action = $_POST['action'] ?? '';

    // Handle Add Customer Record action
    if ($action === 'add_record') {
        // Get form data
        $customer_id = $_POST['customer_id'] ?? '';
        $product = $_POST['product'] ?? '';
        $amount_spent = floatval($_POST['amount_spent'] ?? 0);
        $purchase_date = $_POST['purchase_date'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $invoice_number = $_POST['invoice_number'] ?? '';
        $updated_by = $userId;

        // Validate required fields
        if (empty($customer_id) || empty($product) || empty($invoice_number)) {
            throw new Exception("Please fill all required fields");
        }
        
        // Insert new record - removed sales_rep_id from the query
        $sql = "INSERT INTO customer_record (customer_id, invoice_number, product, amount_spent, purchase_date, notes, updated_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issdssi', $customer_id, $invoice_number, $product, $amount_spent, $purchase_date, $notes, $updated_by);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Record added successfully';
        } else {
            throw new Exception("Error adding record: " . $stmt->error);
        }
        
        $stmt->close();
    }

    // Handle Update Customer Record action
    else if ($action === 'update_customer') {
        // Get form data
        $record_id = $_POST['record_id'] ?? '';
        $customer_id = $_POST['customer_id'] ?? '';
        $product = $_POST['product'] ?? '';
        $amount_spent = floatval($_POST['amount_spent'] ?? 0);
        $purchase_date = $_POST['purchase_date'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $invoice_number = $_POST['invoice_number'] ?? '';
        
        // Validate record ID
        if (empty($record_id)) {
            throw new Exception("Record ID is required");
        }
        
        // Determine if this user can update this record
        $userRole = $_SESSION['role'] ?? '';
        
        if ($userRole === 'admin') {
            // Admin can update any record
            $sql = "UPDATE customer_record SET 
                    customer_id = ?, 
                    product = ?, 
                    amount_spent = ?, 
                    purchase_date = ?, 
                    notes = ?, 
                    invoice_number = ?,
                    updated_by = ?
                    WHERE record_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isdsssii', $customer_id, $product, $amount_spent, $purchase_date, $notes, $invoice_number, $userId, $record_id);
        } else {
            // Sales rep can only update their own records
            $sql = "UPDATE customer_record SET 
                    customer_id = ?, 
                    product = ?, 
                    amount_spent = ?, 
                    purchase_date = ?, 
                    notes = ?, 
                    invoice_number = ?,
                    updated_by = ?
                    WHERE record_id = ? AND updated_by = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isdsssiii', $customer_id, $product, $amount_spent, $purchase_date, $notes, $invoice_number, $userId, $record_id, $userId);
        }
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Record updated successfully';
            } else {
                $response['message'] = 'No changes were made or you do not have permission to edit this record';
            }
        } else {
            throw new Exception("Error updating record: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        throw new Exception("Invalid action specified");
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in save_record.php: " . $e->getMessage());
    
    // Set error message in response
    $response['message'] = $e->getMessage();
}

// Close database connection if open
if (isset($conn) && $conn) {
    $conn->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>