<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get user role from session
$userRole = $_SESSION['role'] ?? '';

// Get input data based on content type
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

// Log the received data for debugging
error_log('Received data: ' . print_r($data, true));

$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        // Handle lead creation
        $customer_id = filter_var($data['customer_id'] ?? 0, FILTER_VALIDATE_INT);
        $sales_rep_id = filter_var($data['sales_rep_id'] ?? 0, FILTER_VALIDATE_INT);
        $lead_status = filter_var($data['lead_status'] ?? 'New', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $followed_up_date = !empty($data['followed_up_date']) ? $data['followed_up_date'] : null;
        $updated_by = $_SESSION['user_id'];

        // Override sales_rep_id for sales reps
        if ($userRole === 'sales_rep') {
            $sales_rep_id = $_SESSION['user_id'];
        }

        if (!$customer_id || !$sales_rep_id) {
            error_log('Missing required fields: customer_id=' . $customer_id . ', sales_rep_id=' . $sales_rep_id);
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }

        $conn = new mysqli($host, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            error_log('Database connection failed: ' . $conn->connect_error);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        // First check if customer exists
        $check_stmt = $conn->prepare("SELECT customer_id FROM customer WHERE customer_id = ?");
        $check_stmt->bind_param("i", $customer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $check_stmt->close();
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Customer ID does not exist']);
            exit;
        }
        $check_stmt->close();
        
        // Now prepare the insert statement
        if ($followed_up_date) {
            $stmt = $conn->prepare("INSERT INTO leads (customer_id, sales_rep_id, lead_status, followed_up_date, updated_by) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $customer_id, $sales_rep_id, $lead_status, $followed_up_date, $updated_by);
        } else {
            // If no date provided, use NULL
            $stmt = $conn->prepare("INSERT INTO leads (customer_id, sales_rep_id, lead_status, updated_by) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $customer_id, $sales_rep_id, $lead_status, $updated_by);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

if ($stmt->execute()) {
    $response = ['success' => true];
    if ($action === 'add') $response['lead_id'] = $conn->insert_id;
    if ($action === 'update') $response['new_status'] = $new_status;
    echo json_encode($response);
    
    // Log success
    error_log("Operation successful: $action");
} else {
    error_log("Database error: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>