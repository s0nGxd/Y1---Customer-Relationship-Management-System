<?php
require_once 'auth.php';
$success_message = "";
$error_message = "";

// Check for success or error messages in session
if (isset($_SESSION['profile_update_success'])) {
    $success_message = $_SESSION['profile_update_success'];
    unset($_SESSION['profile_update_success']);
}

if (isset($_SESSION['profile_update_error'])) {
    $error_message = $_SESSION['profile_update_error'];
    unset($_SESSION['profile_update_error']);
}

// Get user data from database
$user_id = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found
    header("Location: logout.php");
    exit();
}

$user = $result->fetch_assoc();

// Count customers for this sales rep
$customer_count = 0;
if ($user['role'] == 'sales_rep') {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer WHERE sales_rep_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $customer_count = $count_data['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | CRM</title>
    <link rel="stylesheet" href="css/profile.css">
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
                <div class="page-title">User Profile</div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Search...">
                    </div>
                    <div class="user-profile">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-count" id="notificationCount">3</span>
                        </div>
                        <div class="user-avatar" id="headerUserAvatar"><?php echo getInitials($user['name']); ?></div>
                        <div class="user-info">
                            <div class="user-name" id="headerUserName"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="user-role" id="headerUserRole"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Profile Content -->
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-image-container">
                        <div class="profile-image" id="mainUserAvatar">
                            <?php echo getInitials($user['name']); ?>
                        </div>
                        <button class="btn btn-outline btn-edit-photo" id="changePhotoBtn">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <div class="profile-user-info">
                        <h2 id="mainUserName"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <span class="role-badge" id="mainUserRole"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                        <?php if ($user['role'] == 'sales_rep'): ?>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-value" id="customerCount"><?php echo $customer_count; ?></div>
                                <div class="stat-label">Customers</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <nav class="profile-nav">
                        <ul>
                            <li class="profile-nav-item active" data-tab="personal-info">
                                <i class="fas fa-user"></i> Personal Information
                            </li>
                            <li class="profile-nav-item" data-tab="security">
                                <i class="fas fa-lock"></i> Security
                            </li>
                            <li class="profile-nav-item" data-tab="settings">
                                <i class="fas fa-cog"></i> Settings
                            </li>
                        </ul>
                    </nav>
                </div>
                
                <div class="profile-content">
                    
                <!-- Personal Information Tab -->
                <div class="profile-tab active" id="personal-info">
                    <div class="section-header">
                        <h3>Personal Information</h3>
                        <button class="btn btn-outline" id="editPersonalBtn">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </button>
                    </div>
                    
                    <form class="profile-form" id="personalInfoForm" action="update_profile.php" method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone_number']); ?>" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <input type="text" id="role" value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" disabled>
                        </div>
                        <div class="form-actions" style="display: none;" id="personalInfoActions">
                            <button type="button" class="btn btn-secondary" id="cancelPersonalBtn">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="savePersonalBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
                    
                    <!-- Security Tab -->
                    <div class="profile-tab" id="security">
                        <div class="section-header">
                            <h3>Security Settings</h3>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h4>Change Password</h4>
                            </div>
                            <div class="card-body">
                                <form id="changePasswordForm" action="update_password.php" method="POST">
                                    <div class="form-group">
                                        <label for="currentPassword">Current Password</label>
                                        <div class="password-input">
                                            <input type="password" id="currentPassword" name="current_password" required>
                                            <button type="button" class="toggle-password" data-target="currentPassword">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="newPassword">New Password</label>
                                        <div class="password-input">
                                            <input type="password" id="newPassword" name="new_password" required>
                                            <button type="button" class="toggle-password" data-target="newPassword">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirmPassword">Confirm New Password</label>
                                        <div class="password-input">
                                            <input type="password" id="confirmPassword" name="confirm_password" required>
                                            <button type="button" class="toggle-password" data-target="confirmPassword">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="password-strength" id="passwordStrength">
                                        <div class="strength-meter">
                                            <div class="strength-segment"></div>
                                            <div class="strength-segment"></div>
                                            <div class="strength-segment"></div>
                                            <div class="strength-segment"></div>
                                        </div>
                                        <div class="strength-text">Password Strength: <span id="strengthText">None</span></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="changePasswordBtn">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings Tab -->
                    <div class="profile-tab" id="settings">
                        <div class="section-header">
                            <h3>User Settings</h3>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h4>Notifications</h4>
                            </div>
                            <div class="card-body">
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <div class="setting-title">Email Notifications</div>
                                        <div class="setting-description">Receive email notifications for customer updates and sales opportunities.</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="emailNotificationToggle" checked>
                                        <label for="emailNotificationToggle"></label>
                                    </div>
                                </div>
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <div class="setting-title">Task Reminders</div>
                                        <div class="setting-description">Receive reminders for upcoming tasks and deadlines.</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="taskReminderToggle" checked>
                                        <label for="taskReminderToggle"></label>
                                    </div>
                                </div>
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <div class="setting-title">System Notifications</div>
                                        <div class="setting-description">Receive in-app notifications for system updates and announcements.</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="systemNotificationToggle" checked>
                                        <label for="systemNotificationToggle"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Upload Photo Modal -->
    <div class="modal" id="uploadPhotoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Profile Photo</h2>
                <button class="close-btn" id="closePhotoModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="upload-area">
                    <div class="upload-preview" id="photoPreview">
                        <?php echo getInitials($user['name']); ?>
                    </div>
                    <div class="upload-instructions">
                        <p>Upload a square image for best results. Maximum file size 5MB.</p>
                        <label for="photoUpload" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="photoUpload" accept="image/*" style="display: none;">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPhotoBtn">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePhotoBtn">Save Photo</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/profile.js"></script>
</body>
</html>

<?php
// Helper function to get initials from name
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>