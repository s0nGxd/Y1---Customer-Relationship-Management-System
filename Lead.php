<?php
require_once 'auth.php';

// Fetch user role and ID from session
$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? '';

$query = "SELECT 
    l.lead_id,
    l.customer_id,
    c.name AS customer_name,
    c.company,
    l.lead_status,
    l.updated_by,
    u.name AS sales_rep_name,
    l.followed_up_date,
    l.sales_rep_id
FROM leads l
JOIN customer c ON l.customer_id = c.customer_id
JOIN users u ON l.sales_rep_id = u.user_id";

// Add role-based filtering
if ($userRole === 'sales_rep') {
    $query .= " WHERE l.sales_rep_id = ? ORDER BY l.lead_id ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
} else {
    $query .= " ORDER BY l.lead_id ASC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
$leads = $result->fetch_all(MYSQLI_ASSOC);

// Calculate stats
$totalLeads = count($leads);
$newLeads = 0;
$inProgressLeads = 0;

foreach ($leads as $lead) {
    if ($lead['lead_status'] === 'New') $newLeads++;
    if ($lead['lead_status'] === 'In Progress') $inProgressLeads++;
}

// Fetch sales reps for modals
$sales_reps_list = [];
if ($userRole === 'admin') {
    $rep_result = $conn->query("SELECT user_id, name FROM users WHERE role='sales_rep'");
    if ($rep_result) {
        $sales_reps_list = $rep_result->fetch_all(MYSQLI_ASSOC);
        $rep_result->free();
    }
}

$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management | CRM</title>
    <link rel="stylesheet" href="css/lead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        // Pass PHP data to JavaScript
        const serverLeads = <?php echo json_encode(array_map(function($lead) {
            $lead['followed_up_date'] = date('Y-m-d', strtotime($lead['followed_up_date']));
            return $lead;
        }, $leads)); ?>;
        const currentUserId = <?php echo $userId; ?>;
        const userRole = <?php echo json_encode($userRole); ?>;
    </script>
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
                        <a href="calendar.php" class="nav-link">
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

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="header">
                <div class="page-title">Lead Management</div>
                <div class="header-actions">
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
                                    ucfirst(str_replace('_', ' ', $userRole))
                                ); 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Quick Stats -->
            <section class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon leads-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalLeads; ?></div>
                        <div class="stat-label">Total Leads</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon new-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $newLeads; ?></div>
                        <div class="stat-label">New Leads</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon follow-ups-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $inProgressLeads; ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>
            </section>

            <!-- Lead Actions Row -->
            <section class="actions-row">
                <button class="btn btn-primary" id="addLeadBtn">
                    <i class="fas fa-plus"></i> Add Lead
                </button>
                <div class="filter-group">
                    <select id="leadStatusFilter">
                        <option value="all">All Statuses</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="inProgress">In Progress</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </section>

            <!-- Lead Table -->
            <section class="lead-table-section">
                <table class="lead-table">
                    <thead>
                        <tr>
                            <th>LeadID</th>
                            <th>CustomerID</th>
                            <th>Customer</th>   <!--Name-->
                            <th>SalesRepID</th>
                            <th>Status</th>
                            <th>Last Contact</th>  <!--Follow-up Date-->
                            <th>Update By</th>

                        </tr>
                    </thead>
                    <tbody id="leadTableBody">
                        <!------------- Lead rows will be populated by JavaScript ------------------>
                    </tbody>
                </table>
            </section>

            <!-- Status Dropdown Template (Hidden) -->
            <template id="statusDropdownTemplate">
                <div class="status-dropdown">
                    <div class="status-option status-new">New</div>
                    <div class="status-option status-contacted">Contacted</div>
                    <div class="status-option status-inProgress">In Progress</div>
                    <div class="status-option status-closed">Closed</div>
                </div>
            </template>

            <!-- Notification Template (Hidden) -->
            <template id="notificationTemplate">
                <div class="notification">Status updated successfully</div>
            </template>

        </main>
    </div>

    <!-- Add Lead Modal -->
    <div class="modal" id="addLeadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Lead</h2>
                <button class="close-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="addLeadForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_id">Customer ID</label>
                            <input type="number" id="customer_id" name="customer_id" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="follow-up-date">Follow-up Date</label>
                        <input type="date" id="follow-up-date" name="followed_up_date">
                    </div>
                    <div class="form-group">
                        <label for="Assigned">Assigned to</label>
                        <?php if ($userRole === 'admin'): ?>
                            <select id="Assigned" name="sales_rep_id" required>
                                <?php
                                foreach ($sales_reps_list as $rep) {
                                    echo "<option value='{$rep['user_id']}'>{$rep['name']}</option>";
                                }
                                ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="sales_rep_id" value="<?php echo $userId; ?>">
                            <div class="readonly-assigned">Yourself (<?php echo htmlspecialchars($userName); ?>)</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="leadStatus">Status</label>
                            <select id="leadStatus" name="lead_status">
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="inProgress">In Progress</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAddBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Lead</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Lead Modal -->
    <div class="modal" id="editLeadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Lead</h2>
                <button class="close-btn" id="closeEditModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="editLeadForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="Assigned">Assigned to</label>
                            <?php if ($userRole === 'admin'): ?>
                                <select id="editAssigned" name="sales_rep_id" required>
                                    <?php
                                    foreach ($sales_reps_list as $rep) {
                                        echo "<option value='{$rep['user_id']}'>{$rep['name']}</option>";
                                    }
                                    ?>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="sales_rep_id" value="<?php echo $userId; ?>">
                                <div class="readonly-assigned">Yourself (<?php echo htmlspecialchars($userName); ?>)</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editStatus">Status</label>
                            <select id="editStatus" name="lead_status" required>
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="inprogress">In Progress</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editLastContact">Last Contacted</label>
                            <input type="date" id="editLastContact" name="followed_up_date" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

            <!-- View Lead Details Modal -->
            <div class="modal" id="viewLeadModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Lead Details</h2>
                        <button class="close-btn" id="closeViewModalBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <div id="viewLeadContent" style="padding: 20px;">
                            <!-- Content will be populated here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" id="viewEditBtn">Edit Lead</button>
                        <button class="btn btn-primary" id="closeViewDetailsBtn">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/lead.js"></script>
    </body>
    </html>
    <?php $conn->close(); ?>