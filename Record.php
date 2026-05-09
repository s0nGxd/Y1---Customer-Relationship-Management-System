<?php
require_once 'auth.php';

// Fetch user role and ID from session
$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$recordsCount = 0;
$totalRevenue = 0;
$userName = $_SESSION['name'] ?? '';
$recordsData = [];
$customer = []; // Initialize customers array

try {
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
    
    // Fetch customers for dropdown
    $customerSql = "SELECT customer_id, name, company FROM customer";
    $customerStmt = $conn->prepare($customerSql);
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();
    while ($row = $customerResult->fetch_assoc()) {
        $customer[] = $row;
    }
    $customerStmt->close();

    // Fetch records based on role
    if ($userRole === 'admin' || $userRole === 'sales_rep') {
        if ($userRole === 'sales_rep') {
            // For sales rep, only show their customers
            $sql = "SELECT cr.*, c.name, u.name as sales_rep_name
                FROM customer_record cr
                JOIN customer c ON cr.customer_id = c.customer_id
                LEFT JOIN users u ON cr.updated_by = u.user_id
                WHERE cr.updated_by = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
        } else {
            // For admin, show all customers
            $sql = "SELECT cr.*, c.name, u.name as sales_rep_name
                    FROM customer_record cr
                    JOIN customer c ON cr.customer_id = c.customer_id
                    LEFT JOIN users u ON cr.updated_by = u.user_id";
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $recordsData[] = $row;  // Fix: Use recordsData (plural)
        }
        $stmt->close();
        
        // Calculate stats
        $recordsCount = count($recordsData);
        $totalRevenue = 0;
        foreach ($recordsData as $record) {
            $totalRevenue += $record['amount_spent'] ?? 0;
        }
    }

    // Handle success/error messages for alert
    if (isset($_SESSION['success'])) {
        echo '<div class="notification success" id="php-notification">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        echo '<div class="notification error" id="php-notification">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }

    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in record.php: " . $e->getMessage());
    
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
    <title>Sales Records | CRM</title>
    <link rel="stylesheet" href="css/record.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <?php echo $messageScript ?? ''; ?>
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
                <div class="page-title">Customer Records</div>
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
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo htmlspecialchars($recordsCount); ?></div>
                        <div class="stat-label">Total Records</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon opportunities-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">
                            <?php
                            $formattedTotal = '$' . number_format(round($totalRevenue / 1000)) . 'K';
                            echo htmlspecialchars($formattedTotal); 
                            ?>
                        </div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
            </section>

            <!-- Record Actions Row -->
            <section class="actions-row">
                <button class="btn btn-primary" id="addRecordBtn">
                    <i class="fas fa-plus"></i> Add Record
                </button>
                <div class="filter-group">
                    <select id="customerFilter">
                        <option value="all">All Customers</option>
                        <?php foreach ($customer as $cust): ?>
                            <option value="<?php echo htmlspecialchars($cust['customer_id']); ?>">
                                <?php echo htmlspecialchars($cust['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="timeFilter">
                        <option value="all">All Time</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                    <select id="productFilter">
                        <option value="all">All Products</option>
                        <option value="Enterprise Software Suite">Enterprise Software Suite</option>
                        <option value="Cloud Storage Package">Cloud Storage Package</option>
                        <option value="AI Analytics Toolkit">AI Analytics Toolkit</option>
                        <option value="Custom Solution">Custom Solution</option>
                    </select>
                </div>
            </section>

            <!-- Record Table -->
            <section class="record-table-section">
                <table class="record-table" id="RecordTable">
                    <thead>
                        <tr>
                            <th>RecordID</th>
                            <th>Customer</th>
                            <th>Invoice#</th>
                            <th>Product</th>
                            <th>Purchase Date</th>
                            <th>Amount</th>
                            <th>Sales Rep</th>
                        </tr>
                    </thead>
                    <tbody id="recordTableBody">
                        <?php if (empty($recordsData)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px;">
                                    No records found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recordsData as $record): ?>
                                <tr data-record-id="<?php echo htmlspecialchars($record['record_id']); ?>">
                                    <td><?php echo htmlspecialchars($record['record_id']); ?></td>
                                    <td><?php echo htmlspecialchars($record['name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['product']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['purchase_date'])); ?></td>
                                    <td><?php echo '$' . number_format($record['amount_spent'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($record['sales_rep_name'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Add Record Modal -->
    <div class="modal" id="addRecordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Record</h2>
                <button class="close-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="addRecordForm">
                    <div class="form-group">
                        <label for="customerSelect">Customer</label>
                        <select id="customerSelect" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customer as $cust): ?>
                                <option value="<?php echo htmlspecialchars($cust['customer_id']); ?>">
                                    <?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['company']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoiceNumber">Invoice Number</label>
                            <input type="text" id="invoiceNumber" name="invoice_number" required>
                        </div>
                        <div class="form-group">
                            <label for="purchaseDate">Purchase Date</label>
                            <input type="date" id="purchaseDate" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productSelect">Product/Service</label>
                            <select id="productSelect" name="product" required>
                                <option value="">Select Product</option>
                                <option value="Enterprise Software Suite">Enterprise Software Suite</option>
                                <option value="Cloud Storage Package">Cloud Storage Package</option>
                                <option value="AI Analytics Toolkit">AI Analytics Toolkit</option>
                                <option value="Custom Solution">Custom Solution</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount ($)</label>
                            <input type="number" id="amount" name="amount_spent" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="recordNotes">Notes</label>
                        <textarea id="recordNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAddBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record Details Modal -->
    <div class="modal" id="recordDetailsModal" style="display: none;">
        <div class="modal-content" id="recordDetailsContent">
            <!-- Content will be dynamically populated -->
        </div>
    </div>
    
    <!-- Edit Record Modal -->
    <div class="modal" id="editRecordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Record</h2>
                <button class="close-btn" id="closeEditModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="editRecordForm">
                    <input type="hidden" id="editRecordId" name="record_id">
                    
                    <div class="form-group">
                        <label for="editCustomer">Customer</label>
                        <select id="editCustomer" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customer as $cust): ?>
                                <option value="<?php echo htmlspecialchars($cust['customer_id']); ?>">
                                    <?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['company']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editInvoice_number">Invoice Number</label>
                            <input type="text" id="editInvoice_number" name="invoice_number" required>
                        </div>
                        <div class="form-group">
                            <label for="editPurchase_date">Purchase Date</label>
                            <input type="date" id="editPurchase_date" name="purchase_date" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editProduct">Product/Service</label>
                            <select id="editProduct" name="product" required>
                                <option value="">Select Product</option>
                                <option value="Enterprise Software Suite">Enterprise Software Suite</option>
                                <option value="Cloud Storage Package">Cloud Storage Package</option>
                                <option value="AI Analytics Toolkit">AI Analytics Toolkit</option>
                                <option value="Custom Solution">Custom Solution</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editAmount_purchase">Amount ($)</label>
                            <input type="number" id="editAmount_purchase" name="amount_spent" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea id="editNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Record Modal -->
    <div class="modal" id="viewRecordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>View Record</h2>
                <button class="close-btn" id="closeViewModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="viewRecordContent">
                <!-- Will be populated dynamically -->
            </div>
            <div class="modal-footer">
                <button id="viewEditBtn" class="btn btn-outline">Edit Record</button>
                <button id="closeViewDetailsBtn" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const recordsData = <?php echo json_encode($recordsData ?? []); ?>;
    </script>
    <script src="js/navigation.js"></script>
    <script src="js/record.js"></script>
    </body>
</html>