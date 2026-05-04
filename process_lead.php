<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle different lead actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_lead':
                handleAddLead($conn);
                break;
                
            case 'update_lead':
                handleUpdateLead($conn);
                break;
                
            case 'delete_lead':
                handleDeleteLead($conn);
                break;
                
            case 'update_status':
                if (isset($_POST['lead_id'], $_POST['new_status'])) {
                    $lead_id = (int)$_POST['lead_id'];
                    $new_status = $conn->real_escape_string($_POST['new_status']);
                    $updated_by = $_SESSION['user_id'];
                    
                    if (updateLeadStatus($conn, $lead_id, $new_status, $updated_by)) {
                        // Add interaction record for the status change
                        $description = "Status changed to " . $new_status;
                        $stmt = $conn->prepare("INSERT INTO interaction 
                                               (lead_id, interaction_date, interaction_time, description, interaction_type)
                                               VALUES (?, CURDATE(), CURTIME(), ?, 'status_change')");
                        $stmt->bind_param("is", $lead_id, $description);
                        $stmt->execute();
                        
                        echo json_encode(['success' => true]);
                        exit();
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to update lead status']);
                        exit();
                    }
                }
                break;
                
            default:
                $_SESSION['error'] = "Invalid action";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }
    
    header("Location: lead.php");
    exit();
}

function handleAddLead($conn) {
    // Validate input
    $required = ['firstName', 'lastName', 'email', 'leadStatus', 'leadSource', 'salesRep'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, insert the customer
        $stmt = $conn->prepare("INSERT INTO customer 
                               (name, company, email, phone_number, address) 
                               VALUES (?, ?, ?, ?, ?)");
        $fullName = trim($_POST['firstName'] . ' ' . $_POST['lastName']);
        $stmt->bind_param("sssss", 
            $fullName,
            $_POST['company'] ?? null,
            $_POST['email'],
            $_POST['phone'] ?? null,
            $_POST['address'] ?? null
        );
        $stmt->execute();
        $customer_id = $conn->insert_id;
        
        // Then insert the lead
        $stmt = $conn->prepare("INSERT INTO leads 
                               (customer_id, sales_rep_id, lead_status, lead_source, followed_up_date, updated_by) 
                               VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?)");
        $stmt->bind_param("iissi", 
            $customer_id,
            $_POST['salesRep'],
            $_POST['leadStatus'],
            $_POST['leadSource'],
            $_SESSION['user_id']
        );
        $stmt->execute();
        $lead_id = $conn->insert_id;
        
        // Add initial interaction
        $stmt = $conn->prepare("INSERT INTO interaction 
                               (lead_id, interaction_type, interaction_date, interaction_time, description) 
                               VALUES (?, 'note', CURDATE(), CURTIME(), ?)");
        $description = "Lead created through system. Source: " . $_POST['leadSource'];
        $stmt->bind_param("is", $lead_id, $description);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Lead added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleUpdateLead($conn) {
    if (empty($_POST['lead_id'])) {
        throw new Exception("Missing lead ID");
    }
    
    $lead_id = (int)$_POST['lead_id'];
    $sales_rep_id = $_POST['sales_rep_id'] ?? null;
    $lead_status = $_POST['lead_status'] ?? null;
    $follow_up_date = $_POST['follow_up_date'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    // Update lead
    $stmt = $conn->prepare("UPDATE leads 
                           SET sales_rep_id = ?, lead_status = ?, 
                               followed_up_date = ?, updated_by = ?
                           WHERE lead_id = ?");
    $stmt->bind_param("issii", 
        $sales_rep_id,
        $lead_status,
        $follow_up_date,
        $_SESSION['user_id'],
        $lead_id
    );
    $stmt->execute();
    
    // Add interaction if notes were provided
    if (!empty($notes)) {
        $stmt = $conn->prepare("INSERT INTO interaction 
                               (lead_id, interaction_type, interaction_date, interaction_time, description) 
                               VALUES (?, 'note', CURDATE(), CURTIME(), ?)");
        $stmt->bind_param("is", $lead_id, $notes);
        $stmt->execute();
    }
    
    $_SESSION['success'] = "Lead updated successfully!";
}

function handleDeleteLead($conn) {
    if (empty($_POST['lead_id'])) {
        throw new Exception("Missing lead ID");
    }
    
    $lead_id = (int)$_POST['lead_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get customer ID
        $stmt = $conn->prepare("SELECT customer_id FROM leads WHERE lead_id = ?");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $lead = $result->fetch_assoc();
        
        if (!$lead) {
            throw new Exception("Lead not found");
        }
        
        $customer_id = $lead['customer_id'];
        
        // Delete interactions
        $stmt = $conn->prepare("DELETE FROM interaction WHERE lead_id = ?");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        
        // Delete lead
        $stmt = $conn->prepare("DELETE FROM leads WHERE lead_id = ?");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        
        // Delete customer (if no other leads reference them)
        $stmt = $conn->prepare("DELETE FROM customer 
                               WHERE customer_id = ? 
                               AND NOT EXISTS (SELECT 1 FROM leads WHERE customer_id = ?)");
        $stmt->bind_param("ii", $customer_id, $customer_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Lead deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>