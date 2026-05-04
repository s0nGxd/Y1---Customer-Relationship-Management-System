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

// Include database connection
require_once 'db_connect.php';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Fetch all sales reps
    $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE role = 'sales_rep'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sales_reps = [];
    while ($row = $result->fetch_assoc()) {
        $sales_reps[] = $row;
    }
    
    // Return success response with sales reps
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'sales_reps' => $sales_reps]);
    
    // Close connection
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>