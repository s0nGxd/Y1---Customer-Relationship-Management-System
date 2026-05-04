<?php
session_start();
require 'db_connect.php';

ini_set('display_errors', 0);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get current user ID
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';
$userName = '';

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get current user's name
$stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userName = $row['name'];
}
$stmt->close();

// Get and decode the JSON input
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// Handle lead update
if ($data['action'] === 'update_lead') {
    $leadId = intval($data['lead_id']);
    $salesRepId = intval($data['sales_rep_id']);
    $leadStatus = $data['lead_status'];
    $followedUpDate = !empty($data['followed_up_date']) ? $data['followed_up_date'] : null;
    
    // Format lead status properly (capitalize first letter of each word)
    $leadStatus = ucwords($leadStatus);
    
    // If "inprogress" is submitted, convert to "In Progress"
    if (strtolower($leadStatus) === 'inprogress') {
        $leadStatus = 'In Progress';
    }
    
    // Validate lead status
    $validStatuses = ['New', 'Contacted', 'In Progress', 'Closed'];
    if (!in_array($leadStatus, $validStatuses)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid lead status: ' . $leadStatus]);
        exit;
    }
    
    // Check if the user is authorized to update this lead
    if ($userRole === 'sales_rep') {
        // Sales reps can only update their own leads
        $stmt = $conn->prepare("SELECT sales_rep_id FROM leads WHERE lead_id = ?");
        $stmt->bind_param('i', $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row || $row['sales_rep_id'] != $userId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authorized to update this lead']);
            exit;
        }
        
        // Sales reps cannot change the assigned sales rep
        $salesRepId = $row['sales_rep_id'];
    }
    
    // Prepare SQL based on whether follow-up date is provided
    if ($followedUpDate) {
        $stmt = $conn->prepare("UPDATE leads SET sales_rep_id = ?, lead_status = ?, followed_up_date = ?, updated_by = ? WHERE lead_id = ?");
        $stmt->bind_param('issii', $salesRepId, $leadStatus, $followedUpDate, $userId, $leadId);
    } else {
        $stmt = $conn->prepare("UPDATE leads SET sales_rep_id = ?, lead_status = ?, followed_up_date = NULL, updated_by = ? WHERE lead_id = ?");
        $stmt->bind_param('isii', $salesRepId, $leadStatus, $userId, $leadId);
    }
    
    $result = $stmt->execute();
    
    if ($result) {
        // Get the sales rep name
        $salesRepName = '';
        $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $salesRepId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $salesRepName = $row['name'];
        }
        $stmt->close();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Lead updated successfully',
            'lead_status' => $leadStatus,
            'sales_rep_name' => $salesRepName,
            'updated_by' => $userName,
            'followed_up_date' => $followedUpDate
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $stmt->error,
            'sql_error' => $conn->error
        ]);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
exit;
?>