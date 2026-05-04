<?php
// Include database connection
session_start();
require_once 'db_connect.php';

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed.");
}

// Fetch user role and ID from session
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
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

// Function to get sales representatives
function getSalesReps($conn) {
    try {
        $sql = "SELECT user_id, username, name, email, phone_number 
                FROM `users`
                WHERE role = 'sales_rep'
                ORDER BY user_id";
        
        $result = $conn->query($sql);
        
        $salesReps = array();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $salesReps[] = $row;
            }
        }
        
        return $salesReps;
    } catch(Exception $e) {
        die("ERROR: Could not execute query. " . $e->getMessage());
    }
}

// Get sales representatives
$salesReps = getSalesReps($conn);
$totalSalesReps = count($salesReps);

// Function to get initials from name
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}
?>

<!----------------- TEAM.HTML STRUCTURE START ------------------------>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Team | CRM</title>
    <link rel="stylesheet" href="team.css">
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
                        <a href="admin.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="salesteam.php" class="nav-link active">
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
                        <a href="Record.php" class="nav-link">
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
                <div class="page-title">Sales Team Representatives</div>
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

            <!-- Quick Stats for Sales Team -->
            <section class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon sales-team-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalSalesReps;?></div>
                        <div class="stat-label">Sales Representatives</div>
                    </div>
                </div>
            </section>
            
<!-- Sales Team Actions Row -->
<section class="actions-row">
    <button class="btn btn-primary" id="addSalesRepBtn">
        <i class="fas fa-plus"></i> Add Sales Rep
    </button>
</section>

            <!-- Content Panels -->
            <div class="dashboard-panels">
                <!-- Sales Representatives Panel -->
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Sales Representatives</h3>
                        
                    </div>
                    <div class="panel-body">
                        <table class="data-table" id="salesRepsTable">
                            <thead>
                                <tr>
                                    <th>SalesRepID</th>
                                    <th>Representative</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone Number</th>
                                </tr>
                            </thead>

                            <!------ DATABASE FOR SALE REPRESENTATIVE ------>
                            <tbody>
                                <?php if (count($salesReps) > 0): ?>
                                <?php foreach ($salesReps as $rep): ?>

                                <tr>
                                    <td><?php echo str_pad($rep['user_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="user-info-row">
                                            <div class="user-avatar small"><?php echo getInitials($rep['name']); ?></div>
                                            <div><?php echo htmlspecialchars($rep['name']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($rep['username']); ?></td>
                                    <td>
                                        <div class="email-info">
                                            <?php echo htmlspecialchars($rep['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="phone-number">
                                            <?php echo htmlspecialchars($rep['phone_number']); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-data">No sales representative found</td>
                                </tr>
                                <?php endif; ?>
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
                <form id="addSalesRepForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAddBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Representative</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Sales Representative Modal -->
    <div class="modal" id="editSalesRepModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Sales Representative</h2>
                <button class="close-btn" id="closeEditModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="editSalesRepForm">
                    <input type="hidden" id="editRepId">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editFirstName">First Name</label>
                            <input type="text" id="editFirstName" required>
                        </div>
                        <div class="form-group">
                            <label for="editLastName">Last Name</label>
                            <input type="text" id="editLastName" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" id="editEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="editPhone">Phone</label>
                            <input type="tel" id="editPhone">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editUsername">Username</label>
                            <input type="text" id="editUsername" required>
                        </div>
                        
                    </div>
                    <div class="form-group">
                        <label for="editPassword">Reset Password (leave blank to keep current)</label>
                        <input type="password" id="editPassword">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Sales Rep Details Modal -->
    <div class="modal" id="viewSalesRepModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Sales Representative Details</h2>
                <button class="close-btn" id="closeViewModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="rep-profile">
                    <div class="rep-profile-header">
                        <div class="user-avatar large" id="viewRepAvatar">JS</div>
                        <div class="rep-profile-info">
                            <h3 id="viewRepName">John Smith</h3>
                            <p id="viewRepId">SR001</p>
                            <span class="status-badge status-active" id="viewRepStatus">Active</span>
                        </div>
                    </div>
                    <div class="rep-details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Username</div>
                            <div class="detail-value" id="viewRepUsername">jsmith</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value" id="viewRepEmail">john.smith@example.com</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value" id="viewRepPhone">(555) 123-4567</div>
                        </div>
                        

                        <!----
                        <div class="detail-item">
                            <div class="detail-label">Join Date</div>
                            <div class="detail-value" id="viewRepJoinDate">01/15/2023</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Last Login</div>
                            <div class="detail-value" id="viewRepLastLogin">Today at 09:45 AM</div>
                        </div>
                        -->
                    </div>
                    
                </div>
                <div class="form-actions">
                    <button class="btn btn-outline" id="viewEditBtn">Edit Profile</button>
                    <button class="btn btn-primary" id="closeViewDetailsBtn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Leads Modal -->
    <div class="modal" id="assignLeadsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assign Leads to Representatives</h2>
                <button class="close-btn" id="closeAssignModalBtn"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <form id="assignLeadsForm">
                    <div class="form-group">
                        <label for="selectRep">Select Sales Representative</label>
                        <select id="selectRep" required>
                        <option value="">-- Select Representative --</option>
                        <?php foreach ($salesReps as $rep): ?>
                            <option value="SR<?php echo str_pad($rep['user_id'], 3, '0', STR_PAD_LEFT); ?>">
                                <?php echo htmlspecialchars($rep['name']); ?> (SR<?php echo str_pad($rep['user_id'], 3, '0', STR_PAD_LEFT); ?>)
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="leadCount">Number of Leads to Assign</label>
                        <input type="number" id="leadCount" min="1" max="20" value="5">
                    </div>
                    <div class="form-group">
                        <label for="leadPriority">Lead Priority</label>
                        <select id="leadPriority">
                            <option value="high">High Priority</option>
                            <option value="medium" selected>Medium Priority</option>
                            <option value="low">Low Priority</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAssignBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Leads</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="navigation.js"></script>
    <script src="team.js"></script>
</body>
</html>