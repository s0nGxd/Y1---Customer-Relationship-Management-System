<?php
require_once 'auth.php';

// Handle GET request for interaction details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['interaction_id'])) {
    try {
        $interactionDetails = getInteractionDetails(
            $conn, 
            $_GET['interaction_id'], 
            $_SESSION['user_id'], 
            $_SESSION['role']
        );
        
        if ($interactionDetails) {
            // Format dates for display
            $interactionDate = date('F j, Y', strtotime($interactionDetails['interaction_date']));
            $followUpDate = !empty($interactionDetails['follow_up_date']) ? 
                            date('F j, Y', strtotime($interactionDetails['follow_up_date'])) : 'None';
            
            // Output HTML for details
            echo '<div class="details-section">';
            echo '<h3>' . ucfirst($interactionDetails['interaction_type']) . ' with ' . 
                htmlspecialchars($interactionDetails['customer_name']) . '</h3>';
            echo '<p><strong>Date:</strong> ' . $interactionDate . ' at ' . 
                substr($interactionDetails['interaction_time'], 0, 5) . '</p>';
            echo '<p><strong>Company:</strong> ' . htmlspecialchars($interactionDetails['company']) . '</p>';
            echo '<p><strong>Description:</strong> ' . htmlspecialchars($interactionDetails['description']) . '</p>';
            echo '<p><strong>Follow-up Date:</strong> ' . $followUpDate . '</p>';
            echo '</div>';
        } else {
            echo '<p>Interaction not found</p>';
        }
        
        exit(); // Stop further execution of the page
    } catch (Exception $e) {
        echo '<p>Error: ' . $e->getMessage() . '</p>';
        exit();
    }
}

// Current user info
// Get user data from database
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['username'] ?? 'username';
$currentUserRole = $_SESSION['role'] ?? 'sales_rep';

$userName = '';

// Fetch user's name
if ($userId) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userName = $row['name'] ?? '';
    }
    $stmt->close();
}



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                createInteraction($conn, $_POST);
                break;
            case 'update':
                updateInteraction($conn, $_POST);
                break;
            case 'delete':
                if (isset($_POST['interaction_id'])) {
                    deleteInteraction($conn, $_POST['interaction_id']);
                }
                break;
        }
        // Redirect to prevent form resubmission
        header("Location: calendar.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        header("Location: calendar.php");
        exit();
    }
}

// Get all data needed for the page
try {
    $interactions = getInteractions($conn, $currentUserId, $currentUserRole);
    $leads = getLeads($conn, $currentUserId);
    $notifications = getNotifications($conn, $currentUserId);
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Failed to load data: ' . $e->getMessage();
    $interactions = [];
    $leads = [];
    $notifications = [];
}

/**
 * Get all interactions for the current user
 */
function getInteractions($conn, $userId, $userRole) {
    $sql = "SELECT i.interaction_id, i.lead_id, i.interaction_date, i.interaction_time, 
            i.description, i.interaction_type, c.name as customer_name, c.company,
            n.reminder_date, n.follow_up_date
            FROM interaction i
            JOIN leads l ON i.lead_id = l.lead_id
            JOIN customer c ON l.customer_id = c.customer_id
            LEFT JOIN notification n ON i.notification_id = n.reminder_id";
    
    // Add WHERE clause only for sales reps
    if ($userRole === 'sales_rep') {
        $sql .= " WHERE l.sales_rep_id = ?";
    }
    
    $sql .= " ORDER BY i.interaction_date, i.interaction_time";
    
    $stmt = $conn->prepare($sql);
    if ($userRole === 'sales_rep') {
        $stmt->bind_param("i", $userId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $interactions = [];
    while ($row = $result->fetch_assoc()) {
        $interactions[] = $row;
    }
    
    $stmt->close();
    return $interactions;
}

/**
 * Get all leads for the current user
 */
function getLeads($conn, $userId) {
    $sql = "SELECT l.lead_id, c.name, c.company
            FROM leads l
            JOIN customer c ON l.customer_id = c.customer_id
            WHERE l.sales_rep_id = ?
            ORDER BY c.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leads = [];
    while ($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }
    
    $stmt->close();
    return $leads;
}

/**
 * Create a new interaction with optional notification
 */
function createInteraction($conn, $data) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Create notification if reminder is set
        $notificationId = null;
        if (isset($data['set_reminder'])) {
            $sqlNotification = "INSERT INTO notification (lead_id, reminder_date, follow_up_date) 
                            VALUES (?, ?, ?)";
            
            $stmtNotification = $conn->prepare($sqlNotification);
            $stmtNotification->bind_param("iss", 
                $data['lead_id'], 
                $data['interaction_date'], 
                $data['follow_up_date']
            );
            $stmtNotification->execute();
            
            $notificationId = $conn->insert_id;
            $stmtNotification->close();
        }
        
        // Create interaction
        $sqlInteraction = "INSERT INTO interaction (lead_id, notification_id, interaction_time, interaction_date, description, interaction_type) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmtInteraction = $conn->prepare($sqlInteraction);
        $stmtInteraction->bind_param("iissss",
            $data['lead_id'],
            $notificationId,
            $data['interaction_time'],
            $data['interaction_date'],
            $data['description'],
            $data['interaction_type']
        );
        $stmtInteraction->execute();
        $stmtInteraction->close();
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = 'Interaction created successfully';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Failed to create interaction: ' . $e->getMessage();
        throw $e;
    }
}

/**
 * Update an existing interaction and its notification
 */
function updateInteraction($conn, $data) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Get current notification ID if exists
        $stmtCheck = $conn->prepare("SELECT notification_id FROM interaction WHERE interaction_id = ?");
        $stmtCheck->bind_param("i", $data['interaction_id']);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $currentNotification = $result->fetch_assoc();
        $notificationId = $currentNotification['notification_id'] ?? null;
        $stmtCheck->close();
        
        // Handle notification update or creation
        if (isset($data['set_reminder'])) {
            if ($notificationId) {
                // Update existing notification
                $sqlNotification = "UPDATE notification SET 
                                reminder_date = ?, 
                                follow_up_date = ? 
                                WHERE reminder_id = ?";
                
                $stmtNotification = $conn->prepare($sqlNotification);
                $stmtNotification->bind_param("ssi", 
                    $data['interaction_date'], 
                    $data['follow_up_date'], 
                    $notificationId
                );
                $stmtNotification->execute();
                $stmtNotification->close();
            } else {
                // Create new notification
                $sqlNotification = "INSERT INTO notification (lead_id, reminder_date, follow_up_date) 
                                VALUES (?, ?, ?)";
                
                $stmtNotification = $conn->prepare($sqlNotification);
                $stmtNotification->bind_param("iss", 
                    $data['lead_id'], 
                    $data['interaction_date'], 
                    $data['follow_up_date']
                );
                $stmtNotification->execute();
                
                $notificationId = $conn->insert_id;
                $stmtNotification->close();
            }
        } else if ($notificationId) {
            // Remove notification if reminder is not set
            $sqlDeleteNotification = "DELETE FROM notification WHERE reminder_id = ?";
            $stmtDeleteNotification = $conn->prepare($sqlDeleteNotification);
            $stmtDeleteNotification->bind_param("i", $notificationId);
            $stmtDeleteNotification->execute();
            $stmtDeleteNotification->close();
            $notificationId = null;
        }
        
        // Update interaction
        $sqlInteraction = "UPDATE interaction SET 
                        lead_id = ?, 
                        notification_id = ?, 
                        interaction_time = ?, 
                        interaction_date = ?, 
                        description = ?, 
                        interaction_type = ? 
                        WHERE interaction_id = ?";
        
        $stmtInteraction = $conn->prepare($sqlInteraction);
        $stmtInteraction->bind_param("iissssi", 
            $data['lead_id'], 
            $notificationId, 
            $data['interaction_time'], 
            $data['interaction_date'], 
            $data['description'], 
            $data['interaction_type'], 
            $data['interaction_id']
        );
        $stmtInteraction->execute();
        $stmtInteraction->close();
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = 'Interaction updated successfully';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Failed to update interaction: ' . $e->getMessage();
        throw $e;
    }
}

/**
 * Delete an interaction and its notification
 */
function deleteInteraction($conn, $interactionId) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Get notification ID if exists
        $stmtCheck = $conn->prepare("SELECT notification_id FROM interaction WHERE interaction_id = ?");
        $stmtCheck->bind_param("i", $interactionId);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();
        $currentNotification = $result->fetch_assoc();
        $notificationId = $currentNotification['notification_id'] ?? null;
        $stmtCheck->close();
        
        // Delete interaction
        $sqlInteraction = "DELETE FROM interaction WHERE interaction_id = ?";
        $stmtInteraction = $conn->prepare($sqlInteraction);
        $stmtInteraction->bind_param("i", $interactionId);
        $stmtInteraction->execute();
        $stmtInteraction->close();
        
        // Delete notification if exists
        if ($notificationId) {
            $sqlNotification = "DELETE FROM notification WHERE reminder_id = ?";
            $stmtNotification = $conn->prepare($sqlNotification);
            $stmtNotification->bind_param("i", $notificationId);
            $stmtNotification->execute();
            $stmtNotification->close();
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = 'Interaction deleted successfully';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Failed to delete interaction: ' . $e->getMessage();
        throw $e;
    }
}

/**
 * Get upcoming notifications for the current user
 */
function getNotifications($conn, $userId) {
    $today = date('Y-m-d');
    
    $sql = "SELECT n.reminder_id, n.reminder_date, n.follow_up_date, 
            i.interaction_id, i.description, i.interaction_type,
            c.name as customer_name, c.company
            FROM notification n
            JOIN interaction i ON n.reminder_id = i.notification_id
            JOIN leads l ON n.lead_id = l.lead_id
            JOIN customer c ON l.customer_id = c.customer_id
            WHERE l.sales_rep_id = ?
            AND n.follow_up_date >= ?
            ORDER BY n.follow_up_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    return $notifications;
}

/**
 * Get details for a specific interaction
 */
function getInteractionDetails($conn, $interactionId, $userId, $userRole) {
    $sql = "SELECT i.interaction_id, i.lead_id, i.interaction_date, i.interaction_time, 
            i.description, i.interaction_type, c.name as customer_name, c.company,
            n.reminder_id, n.reminder_date, n.follow_up_date
            FROM interaction i
            JOIN leads l ON i.lead_id = l.lead_id
            JOIN customer c ON l.customer_id = c.customer_id
            LEFT JOIN notification n ON i.notification_id = n.reminder_id
            WHERE i.interaction_id = ?";
    
    // Add sales rep restriction if needed
    if ($userRole === 'sales_rep') {
        $sql .= " AND l.sales_rep_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($userRole === 'sales_rep') {
        $stmt->bind_param("ii", $interactionId, $userId);
    } else {
        $stmt->bind_param("i", $interactionId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $interactionDetail = $result->fetch_assoc();
    $stmt->close();
    
    return $interactionDetail;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Meeting Calendar</title>
    <link rel="stylesheet" href="css/calendar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;900&family=Roboto:wght@100;900&family=Tajawal:wght@200;900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">S</div>
                    <span><?php echo ($userRole === 'sales_rep') ? 'Sales CRM' : 'Admin CRM'; ?></span>
                </div>
            </div>
            <nav class="nav-menu">
                <ul>
                    <!-- Dashboard Link -->
                    <li class="nav-item">
                        <a href="<?php echo ($userRole === 'sales_rep') ? 'salesDash.php' : 'admin.php'; ?>" 
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'salesDash.php' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Sales Team (Admin Only) -->
                    <?php if ($userRole === 'admin'): ?>
                    <li class="nav-item">
                        <a href="salesteam.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'salesteam.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-tie"></i>
                            <span>Sales Team</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Common Links -->
                    <li class="nav-item">
                        <a href="customer.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'customer.php' ? 'active' : '' ?>">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="record.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'record.php' ? 'active' : '' ?>">
                            <i class="fas fa-file-invoice"></i>
                            <span>Records</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="lead.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'lead.php' ? 'active' : '' ?>">
                            <i class="fas fa-stream"></i>
                            <span>Leads</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="calendar.php" class="nav-link active">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendar</span>
                        </a>
                    </li>

                    <!-- Profile -->
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li class="nav-item" style="margin-top: auto;">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Calendar Content -->
        <main class="main-content">
            <header class="header">
                <div class="page-title">Sales Calendar</div>
                <div class="header-actions">
                    <button id="new-interaction-btn" class="btn primary">New Interaction</button>
                    <div class="view-toggle">
                        <button id="month-view" class="btn active">Month</button>
                        <button id="week-view" class="btn">Week</button>
                        <button id="day-view" class="btn">Day</button>
                    </div>
                    <div class="category-filter">
                        <select id="type-filter-select">
                            <option value="all">All Types</option>
                            <option value="meeting">Meeting</option>
                            <option value="call">Call</option>
                            <option value="email">Email</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="user-profile">
                        <?php if ($userRole === 'sales_rep') : ?>
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-count">3</span>
                        </div>
                        <?php endif; ?>
                        <div class="user-avatar"><?php echo strtoupper(substr($userName, 0, 2)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="user-role">
                                <?php 
                                echo htmlspecialchars(
                                    ucfirst(str_replace('_', ' ', $currentUserRole))
                                ); 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Calendar Container -->
            <section class="calendar-container">
                <div class="calendar-nav">
                    <button id="prev-btn" class="nav-btn">&lt;</button>
                    <h2 id="current-period"><?php echo date('F Y'); ?></h2>
                    <button id="next-btn" class="nav-btn">&gt;</button>
                </div>

                <!-- Calendar grid container -->
                <div class="calendar-grid" id="calendar-grid">
                    <!-- JavaScript will populate this -->
                </div>
            </section>
            
            <!-- Upcoming Interactions List -->
            <div class="upcoming-interactions">
                <h3>Upcoming Interactions</h3>
                <div class="category-legend">
                    <div class="legend-item"><span class="category-dot category-meeting"></span><span>Meeting</span></div>
                    <div class="legend-item"><span class="category-dot category-call"></span><span>Call</span></div>
                    <div class="legend-item"><span class="category-dot category-email"></span><span>Email</span></div>
                    <div class="legend-item"><span class="category-dot category-other"></span><span>Other</span></div>
                </div>
                <div class="interactions-list">
                    <?php
                    // Sort interactions by date
                    usort($interactions, function($a, $b) {
                        return strtotime($a['interaction_date'] . $a['interaction_time']) <=> strtotime($b['interaction_date'] . $b['interaction_time']);
                    });
                    
                    // Filter future interactions
                    $today = date('Y-m-d');
                    $upcomingInteractions = array_filter($interactions, function($interaction) use ($today) {
                        $interactionDateTime = new DateTime($interaction['interaction_date'] . ' ' . $interaction['interaction_time']);
                        $now = new DateTime();
                        return $interactionDateTime > $now;
                    });
                    
                    // Display up to 5 upcoming interactions
                    if (empty($upcomingInteractions)) {
                        echo '<div class="no-interactions">No upcoming interactions</div>';
                    } else {
                        $count = 0;
                        foreach ($upcomingInteractions as $interaction) {
                            if ($count >= 5) break;
                            
                            $date = new DateTime($interaction['interaction_date']);
                            $now = new DateTime();
                            $diff = $now->diff($date);
                            
                            $dateText = '';
                            if ($diff->days == 0) {
                                $dateText = 'Today';
                            } elseif ($diff->days == 1) {
                                $dateText = 'Tomorrow';
                            } else {
                                $dateText = $date->format('M j, Y');
                            }
                            
                            echo '<div class="interaction-item" onclick="showInteractionDetails(' . $interaction['interaction_id'] . ')">';
                            echo '<div class="interaction-date">' . $dateText . '</div>';
                            echo '<div class="interaction-time">' . substr($interaction['interaction_time'], 0, 5) . '</div>';
                            echo '<div class="interaction-type category-' . $interaction['interaction_type'] . '">' . ucfirst($interaction['interaction_type']) . '</div>';
                            echo '<div class="interaction-customer">' . htmlspecialchars($interaction['customer_name']) . '</div>';
                            echo '</div>';
                            
                            $count++;
                        }
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Interaction Form Modal -->
    <div id="interaction-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Schedule an Interaction</h2>
            <form id="interaction-form" method="post" action="calendar.php">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="interaction_id" id="interaction-id">
                <div class="form-group">
                    <label for="lead-select">Lead:</label>
                    <select id="lead-select" name="lead_id" required>
                        <option value="">Select a lead</option>
                        <?php foreach ($leads as $lead): ?>
                            <option value="<?php echo $lead['lead_id']; ?>">
                                <?php echo htmlspecialchars($lead['name'] . ' (' . $lead['company'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="interaction-date">Follow-up Date:</label>
                    <input type="date" id="interaction-date" name="interaction_date" required>
                </div>
                <div class="form-group">
                    <label for="interaction-time">Time:</label>
                    <input type="time" id="interaction-time" name="interaction_time" required>
                </div>
                <div class="form-group">
                    <label for="interaction-type">Type:</label>
                    <select id="interaction-type" name="interaction_type" required>
                        <option value="meeting">Meeting</option>
                        <option value="call">Call</option>
                        <option value="email">Email</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="interaction-description">Description:</label>
                    <textarea id="interaction-description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="set-reminder">Set Reminder:</label>
                    <input type="checkbox" id="set-reminder" name="set_reminder" checked>
                </div>
                <div id="reminder-details" class="form-group">
                    <label for="follow-up-date">Reminder Date:</label>
                    <input type="date" id="follow-up-date" name="follow_up_date">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn primary">Save Interaction</button>
                    <button type="button" id="cancel-btn" class="btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Notification Panel -->
    <div id="notification-panel" class="notification-panel">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button id="close-notifications" class="btn small">&times;</button>
        </div>
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">No notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo ($notification['days_remaining'] <= 2) ? 'urgent' : ''; ?>"
                         onclick="showInteractionDetails(<?php echo $notification['interaction_id']; ?>)">
                        <div class="notification-icon"><i class="fas fa-bell"></i></div>
                        <div class="notification-content">
                            <div class="notification-title">Follow-up with <?php echo htmlspecialchars($notification['customer_name']); ?></div>
                            <div class="notification-details"><?php echo htmlspecialchars($notification['description']); ?></div>
                            <div class="notification-date">
                                Due: <?php echo date('M j, Y', strtotime($notification['follow_up_date'])); ?>
                                <?php 
                                    $followUpDate = new DateTime($notification['follow_up_date']);
                                    $today = new DateTime();
                                    $daysLeft = $followUpDate->diff($today)->days;
                                    $daysLeft = $followUpDate > $today ? $daysLeft : 0;
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Interaction Details Modal -->
    <div id="interaction-details-modal" class="modal">
        <div class="modal-content">
            <span class="close-details">&times;</span>
            <h2>Interaction Details</h2>
            <div id="interaction-details-content">
                <!-- Interaction details will be populated by JavaScript -->
            </div>
            <div class="form-actions">
                <button id="edit-interaction-btn" class="btn primary">Edit Interaction</button>
                <form id="delete-form" method="post" action="calendar.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="interaction_id" id="delete-interaction-id">
                    <button type="submit" id="delete-interaction-btn" class="btn danger">Delete Interaction</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>


    <script>
    // Pass PHP data to JavaScript with proper date formatting
    const serverInteractions = <?php echo json_encode(array_map(function($interaction) {
        $date = new DateTime($interaction['interaction_date']);
        return [
            'interaction_id' => $interaction['interaction_id'] ?? null,
            'interaction_date' => $date->format('Y-m-d'), // Ensure consistent format
            'interaction_time' => $interaction['interaction_time'] ?? null,
            'customer_name' => $interaction['customer_name'] ?? null,
            'interaction_type' => $interaction['interaction_type'] ?? null,
            'description' => $interaction['description'] ?? null
        ];
    }, $interactions ?? [])); ?>;
</script>
    <script src="js/calendar.js"></script>
    </body>
    </html>
    <?php $conn->close(); ?>