<?php
require_once 'auth.php';
require_role('sales_rep');

// Get the current user's ID
$current_user_id = $_SESSION['user_id'];

// Get the current user's info
$user_info = [];
$query = "SELECT name, email, phone_number 
            FROM users 
            WHERE user_id = ?";
$stmt = $conn ->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
}
$stmt->close();

// Get count of customers assigned to current sales rep
$my_customers_count = 0;
$query = "SELECT COUNT(*) as count 
          FROM customer
          WHERE sales_rep_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $my_customers_count = $row['count'];
}
$stmt->close();

// Get new leads count for current sales rep
$new_leads_count = 0;
$query = "SELECT COUNT(*) as count 
          FROM leads 
          WHERE sales_rep_id = ? AND lead_status = 'New'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_leads_count = $row['count'];
}
$stmt->close();

// Get follow-ups due today for current sales rep
$today = date('Y-m-d');
$follow_ups_count = 0;
$query = "SELECT COUNT(*) as count 
          FROM notification n
          JOIN leads l ON n.lead_id = l.lead_id
          WHERE l.sales_rep_id = ? AND n.follow_up_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $current_user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $follow_ups_count = $row['count'];
}
$stmt->close();

// Get notifications for current sales rep
$notifications = [];
$query = "SELECT n.reminder_id, c.name AS customer_name, n.reminder_date, n.follow_up_date, 
          l.lead_status, DATEDIFF(CURRENT_DATE, n.reminder_date) AS days_ago
          FROM notification n
          JOIN leads l ON n.lead_id = l.lead_id
          JOIN customer c ON l.customer_id = c.customer_id
          WHERE l.sales_rep_id = ?
          ORDER BY n.reminder_date DESC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Get today's schedule (today's follow-ups)
$schedule = [];
$query = "SELECT n.reminder_id, c.name AS customer_name, c.company,
          i.interaction_type, i.interaction_time, i.description
          FROM notification n
          JOIN leads l ON n.lead_id = l.lead_id
          JOIN customer c ON l.customer_id = c.customer_id
          LEFT JOIN interaction i ON n.reminder_id = i.notification_id
          WHERE l.sales_rep_id = ? AND n.follow_up_date = ?
          ORDER BY i.interaction_time";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $current_user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $schedule = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Get recent leads for current sales rep
$recent_leads = [];
$query = "SELECT l.lead_id, c.name AS customer_name, c.company, 
          l.lead_status, l.followed_up_date
          FROM leads l
          JOIN customer c ON l.customer_id = c.customer_id
          WHERE l.sales_rep_id = ?
          ORDER BY l.followed_up_date DESC
          LIMIT 4";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $recent_leads = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

$conn->close();

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'New':
            return 'status-lead';
        case 'Contacted':
        case 'In Progress':
            return 'status-prospect';
        case 'Closed':
            return 'status-customer';
        default:
            return 'status-lead';
    }
}

// Helper function to get time ago text
function getTimeAgo($days) {
    if ($days == 0) {
        return "Today";
    } elseif ($days == 1) {
        return "Yesterday";
    } else {
        return "$days days ago";
    }
}

// Helper function to get notification icon
function getNotificationIcon($status) {
    switch ($status) {
        case 'New':
            return 'fas fa-user-plus';
        case 'Contacted':
            return 'fas fa-phone';
        case 'In Progress':
            return 'fas fa-calendar-check';
        case 'Closed':
            return 'fas fa-check-circle';
        default:
            return 'fas fa-exclamation-circle';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard | CRM</title>
    <link rel="stylesheet" href="css/salesDash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">S</div>
                    <span>Sales CRM</span>
                </div>
            </div>
            <nav class="nav-menu">
                <ul>
                    <li class="nav-item">
                        <a href="salesDash.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="customer.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="record.php" class="nav-link">
                            <i class="fas fa-file-invoice"></i>
                            <span>Records</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="lead.php" class="nav-link">
                            <i class="fas fa-stream"></i>
                            <span>Leads</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="calendar.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Calendar</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
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

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="header">
                <div class="page-title">Sales Dashboard</div>
                <div class="header-actions">
                    <div class="user-profile">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-count"><?php echo count($notifications); ?></span>
                        </div>

                        <div class="user-avatar">
                            <?php 
                            //Display user initials
                            if (!empty($user_info['name'])) {
                                $initials = explode(' ', $user_info['name']);
                                echo strtoupper(substr($initials[0],0,1));
                                if(count($initials) > 1){
                                    echo strtoupper(substr($initials[count($initials)-1], 0, 1));
                                }
                            } else{
                                echo "U";
                            }
                            ?>
                        </div>

                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user_info['name'] ?? 'User'); ?></div>
                            <div class="user-role">Sales Rep</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Quick Stats -->
            <section class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon customers-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $my_customers_count; ?></div>
                        <div class="stat-label">My Customers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon leads-icon">
                        <i class="fas fa-stream"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $new_leads_count; ?></div>
                        <div class="stat-label">New Leads</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon follow-ups-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $follow_ups_count; ?></div>
                        <div class="stat-label">Follow-ups Today</div>
                    </div>
                </div>
                
            </section>

            <!-- Content Panels -->
            <div class="dashboard-panels">
                
                <!-- Notifications & Reminders -->
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Notifications & Reminders</h3>
                        <a href="calendar.php" class="view-all">View All</a>
                    </div>
                    <div class="panel-body">
                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="empty-message">No notification at this time</div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo ($notification['days_ago'] <= 1) ? 'new' : ''; ?>">
                                        <div class="notification-icon">
                                            <i class="<?php echo getNotificationIcon($notification['lead_status']); ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title">
                                                <?php 
                                                switch ($notification['lead_status']) {
                                                    case 'New':
                                                        echo 'New Lead Assigned';
                                                        break;
                                                    case 'Contacted':
                                                        echo 'Follow-up Required';
                                                        break;
                                                    case 'In Progress':
                                                        echo 'Lead Progressing';
                                                        break;
                                                    case 'Closed':
                                                        echo 'Lead Closed';
                                                        break;
                                                    default:
                                                        echo 'Notification';
                                                }
                                                ?>
                                            </div>
                                            <div class="notification-text">
                                                <?php echo htmlspecialchars($notification['customer_name']); ?> - 
                                                <?php 
                                                if ($notification['follow_up_date'] == $today) {
                                                    echo "Follow-up due today";
                                                } else {
                                                    echo "Follow-up on " . date('M j, Y', strtotime($notification['follow_up_date']));
                                                }
                                                ?>
                                            </div>
                                            <div class="notification-time"><?php echo getTimeAgo($notification['days_ago']); ?></div>
                                        </div>
                                        <div class="notification-actions">
                                            <button class="btn-icon"><i class="fas fa-check"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Today's Schedule Panel -->
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Today's Schedule</h3>
                        <button class="btn btn-outline" onclick="window.location.href='calendar.php';">
                            View Calendar
                        </button>
                    </div>
                    <div class="panel-body">
                        <div class="schedule-timeline">
                            <?php if (empty($schedule)): ?>
                                <div class="empty-message">No scheduled events for today.</div>
                            <?php else: ?>
                                <?php foreach($schedule as $index => $event): ?>
                                    <?php
                                    $time_display = "-";
                                    if (!empty($event['interaction_time'])) {
                                        $time_display = date('g:i A', strtotime($event['interaction_time']));
                                    };
                                    ?>
                            <div class="timeline-item <?php echo ($index == 2) ? 'active' :''; ?>">
                                <div class="timeline-time"><?php echo $time_display; ?></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <?php 
                                            if (!empty($event['interaction_type'])) {
                                                switch ($event['interaction_type']) {
                                                    case 'call':
                                                        echo 'Client Call';
                                                        break;
                                                    case 'meeting':
                                                        echo 'Meeting';
                                                        break;
                                                    case 'email':
                                                        echo 'Send Email';
                                                        break;
                                                    default:
                                                        echo 'Follow-up';
                                                }
                                            } else {
                                                echo 'Follow-up';
                                            }
                                        ?>
                                    </div>
                                    <div class="timeline-details">
                                        <?php echo htmlspecialchars($event['customer_name']); ?> - 
                                        <?php echo htmlspecialchars($event['company'] ?? ''); ?>
                                        <?php if (!empty($event['description'])): ?>
                                            <br><?php echo htmlspecialchars($event['description']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                        </div>
                    </div>
                </section>
                
                <!-- Recent Leads Panel -->
                <section class="panel full-width-panel">
                    <div class="panel-header">
                        <h3 class="panel-title">My Recent Leads</h3>
                        <button class="btn btn-outline" onclick="window.location.href='lead.php';">
                             All
                        </button>
                    </div>
                    <div class="panel-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Lead</th>
                                    <th>Company</th>
                                    <th>Date Added</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_leads)): ?>
                                    <tr>
                                        <td colspan="4" class="empty-message">No leads assigned to you yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_leads as $lead): ?>
                                <tr>
                                    <td>
                                        <div class="lead-info">
                                            <div class="lead-contact"><?php echo htmlspecialchars($lead['customer_name']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($lead['company'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($lead['followed_up_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusBadgeClass($lead['lead_status']); ?>">
                                            <?php echo $lead['lead_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <script src="js/navigation.js"></script>
    <script src="js/salesDash.js"></script>
</body>
</html>