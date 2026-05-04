<?php
// Display all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Initialize variables
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$customerCount = 0;
$userName = '';
$customerData = [];

try {
    // Include database connection
    require_once 'db_connect.php';
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Function to get initials
    function getInitials($name) {
        $nameParts = explode(' ', $name);
        $initials = '';
        if (!empty($nameParts[0])) {
            $initials .= substr($nameParts[0], 0, 1);
        }
        if (!empty($nameParts[1])) {
            $initials .= substr($nameParts[1], 0, 1);
        }
        return strtoupper($initials);
    }

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

    // Fetch customers based on role
    if ($userRole === 'admin' || $userRole === 'sales_rep') {
        if ($userRole === 'sales_rep') {
            // For sales rep, only show their customers with lead status
            $sql = "SELECT c.*, l.lead_status 
                    FROM customer c 
                    LEFT JOIN leads l ON c.customer_id = l.customer_id 
                    WHERE c.sales_rep_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
        } else {
            // For admin, show all customers with lead status
            $sql = "SELECT c.*, l.lead_status 
                    FROM customer c 
                    LEFT JOIN leads l ON c.customer_id = l.customer_id";
            $stmt = $conn->prepare($sql);
        }
        
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $customerData[] = $row;
        }
        $stmt->close();
        
        $customerCount = count($customerData);
    }

    // Handle success/error messages for alert
    $messageScript = '';
    if (isset($_SESSION['success'])) {
        $messageScript = '<script>
            document.addEventListener("DOMContentLoaded", function() {
                alert(' . json_encode($_SESSION['success']) . ');
            });
        </script>';
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        $messageScript = '<script>
            document.addEventListener("DOMContentLoaded", function() {
                alert(' . json_encode($_SESSION['error']) . ');
            });
        </script>';
        unset($_SESSION['error']);
    }

    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in customer.php: " . $e->getMessage());
    
    // Display error for debugging
    echo "An error occurred: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management | CRM</title>
    <link rel="stylesheet" href="customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php echo $messageScript ?? ''; ?>

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
                <div class="page-title">Customer Management</div>
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
                    <div class="stat-icon customers-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo htmlspecialchars($customerCount); ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
            </section>

            <!-- Customer Actions Row -->
            <section class="actions-row">
                <button class="btn btn-primary" id="addCustomerBtn">
                    <i class="fas fa-plus"></i> Add Customer
                </button>
                <div class="filter-group">
                    <select id="customerStatusFilter">
                        <option value="all">All Statuses</option>
                        <option value="New">New</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Contacted">Contacted</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </section>

            <!-- Content Panels -->
            <div class="dashboard-panels">
                <section class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title">Customers</h3>
                    </div>
                    <div class="panel-body">
                        <table class="data-table" id="CustomersTable">
                            <thead>
                                <tr>
                                    <th>CustomerID</th>
                                    <th>Customer</th>
                                    <th>Company</th>
                                    <th>Email</th>
                                    <th>Phone Number</th>
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($customerData) > 0): ?>
                                <?php foreach ($customerData as $cust): ?>
                                    <tr data-status="<?php echo !empty($cust['lead_status']) ? strtolower($cust['lead_status']) : ''; ?>">
                                    <td><?php echo str_pad($cust['customer_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="user-info-row">
                                            <div class="user-avatar small"><?php echo getInitials($cust['name']); ?></div>
                                            <div><?php echo htmlspecialchars($cust['name']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($cust['company']); ?></td>
                                    <td>
                                        <div class="email-info">
                                            <?php echo htmlspecialchars($cust['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="phone-number">
                                            <?php echo htmlspecialchars($cust['phone_number']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="address">
                                            <?php echo htmlspecialchars($cust['address']); ?>
                                        </div>
                                    </td>
                                    <!-- Hidden cell for status -->
                                    <td class="status-cell" style="display: none;"><?php echo !empty($cust['lead_status']) ? $cust['lead_status'] : ''; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-data">No customers found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        <!-- Add Customer Modal -->
<div class="modal" id="addCustomerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Customers</h2>
            <button class="close-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="addCustomerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="company">Company</label>
                        <input type="text" id="company" required>
                    </div>
                </div>
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
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address">
                    </div>
                </div>
                <!-- Sales Rep Selection (Admin Only) -->
                <?php if ($userRole === 'admin'): ?>
                <div class="form-row" id="salesRepSelectContainer" style="display: none;">
                    <div class="form-group">
                        <label for="salesRepSelect">Assign to Sales Representative</label>
                        <select id="salesRepSelect" required>
                            <option value="">Select Sales Representative</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelAddBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Edit Customer Modal -->
<div class="modal" id="editCustomerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Customer</h2>
            <button class="close-btn" id="closeEditModalBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="editCustomerForm">
                <input type="hidden" id="editCustId">
                <div class="form-row">
                    <div class="form-group">
                        <label for="editCompany">Company</label>
                        <input type="text" id="editCompany">
                    </div>
                </div>
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
                        <label for="editaddress">Address</label>
                        <input type="text" id="editaddress" required>
                    </div>
                </div>
                <!-- Sales Rep Selection (Admin Only) -->
                <?php if ($userRole === 'admin'): ?>
                <div class="form-row" id="editSalesRepSelectContainer" style="display: none;">
                    <div class="form-group">
                        <label for="editSalesRepSelect">Assign to Sales Representative</label>
                        <select id="editSalesRepSelect" required>
                            <option value="">Select Sales Representative</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- View Customers Details Modal -->
    <div class="modal" id="viewCustomerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Customer Details</h2>
                <button class="close-btn" id="closeViewModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="cust-profile">
                    <div class="cust-profile-header">
                        <div class="user-avatar large" id="viewCustAvatar">SR</div>
                        <div class="cust-profile-info">
                            <h3 id="viewCustName">Shen ROng</h3>
                            <p id="viewCustId">SR001</p>
                        </div>
                    </div>
                    <div class="cust-details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Company</div>
                            <div class="detail-value" id="viewCustUsername">jsmith</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value" id="viewCustEmail">john.smith@example.com</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value" id="viewCustPhone">(555) 123-4567</div>
                        </div>
                    </div>
                    
                </div>
                <div class="form-actions">
                    <button class="btn btn-outline" id="viewEditBtn">Edit Profile</button>
                    <button class="btn btn-primary" id="closeViewDetailsBtn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="navigation.js"></script>
    <script src="customer.js"></script>
</body>
</html>