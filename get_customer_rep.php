<?php
// Start session
session_start();

// Initialize variables
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Check if user is admin
if (!$userId || $userRole !== 'admin') {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if customer_id is provided
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
    exit;
}

$customer_id = intval($_GET['customer_id']);

// Include database connection
require_once 'db_connect.php';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Fetch customer's sales rep
    $stmt = $conn->prepare("SELECT sales_rep_id FROM customer WHERE customer_id = ?");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Return success response with sales rep id
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'sales_rep_id' => $row['sales_rep_id']]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
    }
    
    // Close connection
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>