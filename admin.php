<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// search data for later use (sales team overview)
require_once 'db_connect.php';

// Get top 4 sales reps by number of customers
$sales_reps = [];
$query = "SELECT u.user_id, u.name, u.email, u.phone_number, COUNT(c.customer_id) AS total_customers
          FROM users u
          LEFT JOIN customer c ON u.user_id = c.sales_rep_id
          WHERE u.role = 'sales_rep'
          GROUP BY u.user_id
          ORDER BY total_customers DESC
          LIMIT 4";

$result = $conn->query($query);
if ($result) {
    $sales_reps = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Get sales rep count
$sales_rep_count = 0;
$result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'sales_rep'");
if ($result) {
    $row = $result->fetch_assoc();
    $sales_rep_count = $row['count'];
    $result->free();
}

// Get total customers count
$customer_count = 0;
$result = $conn->query("SELECT COUNT(*) AS count FROM customer");
if ($result) {
    $row = $result->fetch_assoc();
    $customer_count = $row['count'];
    $result->free();
}

// Get latest leads
$leads = [];
$query = "SELECT l.lead_id, c.name AS customer_name, l.followed_up_date, 
                 u.name AS sales_rep_name, l.lead_status
          FROM leads l
          JOIN customer c ON l.customer_id = c.customer_id
          JOIN users u ON l.sales_rep_id = u.user_id
          ORDER BY l.followed_up_date DESC
          LIMIT 4";

$result = $conn->query($query);
if ($result) {
    $leads = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CRM</title>

    <!-- STYLESHEET -->
    <link rel="stylesheet" href="admin.css">
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
                    <span>Admin CRM</span>
                </div>
            </div>
            <nav class="nav-menu">
                <ul>
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link active">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="salesteam.php" class="nav-link">
                            <i class="fas fa-user-tie"></i>
                            <span>Sales Team</span>
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
                        <a href="Lead.php" class="nav-link">
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
            <div class="page-title">Admin Dashboard</div>
            <div class="header-actions">
                <div class="user-profile">
                    <?php
                    // Generate avatar initials
                    $name_parts = explode(' ', $_SESSION['name']);
                    $initials = '';
                    foreach ($name_parts as $part) {
                        $initials .= strtoupper(substr($part, 0, 1));
                    }
                    $avatar = substr($initials, 0, 2);
                    ?>
                    <div class="user-avatar"><?= $avatar ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                        <div class="user-role">Admin</div>
                    </div>
                </div>
            </div>
        </header>

            <!-- Dashboard Quick Stats -->
            <section class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon sales-team-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $sales_rep_count; ?></div>
                        <div class="stat-label">Sales Representatives</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon customers-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $customer_count; ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
            </section>
            
            <!-- Admin Quick Actions -->
            <section class="actions-panel">
                <h3 class="section-title">Quick Actions</h3>
                <div class="action-cards">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="action-title">Add Sales Rep</div>
                    </div>
                </div>
            </section>

            <!-- Content Panels -->
            <div class="dashboard-panels">
                <!-- Sales Team Overview Panel -->
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Sales Team Overview</h3>
                        <a href="salesteam.php" class="btn btn-outline">View All</a>
                    </div>
                    <div class="panel-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Representative</th>
                                    <th>Customers</th>
                                    <th>Email</th>
                                    <th>Phone Number</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_reps as $rep): 
                                    $initials = '';
                                    $name_parts = explode(' ', $rep['name']);
                                    foreach ($name_parts as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-info-row">
                                            <div class="user-avatar small"><?= substr($initials, 0, 2) ?></div>
                                            <div><?= htmlspecialchars($rep['name']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= $rep['total_customers'] ?></td>
                                    <td>
                                        <div class="email-info">
                                            <?= htmlspecialchars($rep['email']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="phone-number">
                                            <?= $rep['phone_number'] ?? 'N/A' ?>
                                        </div>
                                    </td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <!-- Recent Leads Panel -->
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Recent Leads</h3>
                        <a href="Lead.php" class="btn btn-outline">View All</a>
                    </div>
                    <div class="panel-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>LeadID</th>
                                    <th>Customer</th>
                                    <th>Follow-up Date</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads as $lead): 
                                    $status_class = match($lead['lead_status']) {
                                        'New' => 'status-lead',
                                        'Contacted' => 'status-prospect',
                                        'In Progress' => 'status-progress',
                                        'Closed' => 'status-closed',
                                        default => ''
                                    };
                                ?>
                                <tr>
                                    <td>#<?= $lead['lead_id'] ?></td>
                                    <td><?= htmlspecialchars($lead['customer_name']) ?></td>
                                    <td><?= date('M j, Y', strtotime($lead['followed_up_date'])) ?></td>
                                    <td><?= htmlspecialchars($lead['sales_rep_name']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $lead['lead_status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Add Sales Representative Modal -->
    <div class="modal" id="addSalesRepModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Sales Representative</h2>
                <button class="close-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="addSalesRepForm" action="add_salesrep.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" required> <!-- Added name -->
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required> <!-- Added name -->
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required> <!-- Added name -->
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone"> <!-- Added name -->
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username"> <!-- Added name -->
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"> <!-- Added name -->
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAddBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Representative</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="navigation.js"></script>
    <script src="admin.js"></script>
</body>
</html>